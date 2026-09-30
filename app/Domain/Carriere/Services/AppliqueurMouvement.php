<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Affectation;
use Illuminate\Support\Facades\DB;

class AppliqueurMouvement
{
    public function __construct(
        private GenerateurInstantane $generateur,
    ) {}

    /**
     * Applique le mouvement à la date d'effet : met à jour l'affectation
     * et la situation de carrière, en conservant les instantanés avant/après.
     */
    public function appliquer(MouvementCarriere $mouvement): MouvementCarriere
    {
        if (! $mouvement->peutEtreApplique()) {
            throw new \DomainException(
                "Le mouvement {$mouvement->numero_mouvement} n'est pas prêt à être appliqué."
            );
        }

        return DB::transaction(function () use ($mouvement) {
            // 1. Capturer l'état AVANT
            $avant = $this->generateur->capturerAvant($mouvement);

            // 2. Appliquer les changements selon le type.
            // Les actes temporaires (intérim, détachement…) ne modifient pas le poste
            // permanent (CDC §3.4) : ils restent suivis dans le registre des mouvements.
            if ($mouvement->estAffectation()) {
                $this->appliquerAffectation($mouvement);
            }

            if ($mouvement->estCarriere()) {
                $this->appliquerEvolutionCarriere($mouvement);
            }

            // 3. Capturer l'état APRÈS
            $apres = $this->generateur->capturerAvant($mouvement);

            // 4. Enregistrer l'instantané
            $this->generateur->enregistrer($mouvement, $avant, $apres);

            // 5. Passer le mouvement au statut « effectif »
            $mouvement->update([
                'statut' => StatutMouvement::EFFECTIF->value,
            ]);

            return $mouvement->fresh();
        });
    }

    /**
     * Ferme l'affectation courante et en crée une nouvelle.
     */
    private function appliquerAffectation(MouvementCarriere $mouvement): void
    {
        $courante = Affectation::query()
            ->where('salarie_id', $mouvement->salarie_id)
            ->where('en_cours', true)
            ->latest('date_debut')
            ->first();

        // Un acte peut ne changer que la structure ou que le poste : le reste est repris.
        $structureId = $mouvement->structure_cible_id ?? $courante?->structure_id;
        $posteId = $mouvement->poste_cible_id ?? $courante?->poste_id;

        if (! $structureId || ! $posteId) {
            throw new \DomainException(
                "Le mouvement {$mouvement->numero_mouvement} ne précise ni structure ni poste cible, "
                .'et le salarié n\'a pas d\'affectation courante à reprendre.'
            );
        }

        // Fermer l'affectation en cours
        Affectation::query()
            ->where('salarie_id', $mouvement->salarie_id)
            ->where('en_cours', true)
            ->update([
                'en_cours' => false,
                'date_fin' => $mouvement->date_effet,
            ]);

        // Créer la nouvelle affectation
        Affectation::create([
            'salarie_id' => $mouvement->salarie_id,
            'structure_id' => $structureId,
            'poste_id' => $posteId,
            'date_debut' => $mouvement->date_effet,
            'en_cours' => true,
            'etat' => 1,
        ]);

        // Mettre à jour le lieu d'affectation sur la fiche salarié (s'il change)
        if ($mouvement->lieu_affectation_cible) {
            $mouvement->salarie->update([
                'lieu_affectation' => $mouvement->lieu_affectation_cible,
            ]);
        }
    }

    /**
     * Met à jour la situation de carrière (classification + dates).
     */
    private function appliquerEvolutionCarriere(MouvementCarriere $mouvement): void
    {
        $nouvellePosition = $mouvement->position_classification_cible_id;

        // Sans position cible, l'acte ne touche pas à la classification.
        if (! $nouvellePosition) {
            return;
        }

        $situation = SituationCarriere::firstOrCreate(
            ['salarie_id' => $mouvement->salarie_id],
            [
                'entreprise_id' => $mouvement->entreprise_id,
                'enregistre_le' => now(),
                'type_source' => TypeSourceSituation::ACTE->value,
                'statut_historique' => 'partial',
                'statut_fiabilite' => StatutFiabilite::A_CONFIRMER->value,
                'etat' => 1,
            ]
        );

        $ancienne = $situation->position_classification_id
            ? PositionClassification::find($situation->position_classification_id)
            : null;
        $nouvelle = PositionClassification::find($nouvellePosition);

        // Seules les dimensions qui changent prennent la date d'effet de l'acte :
        // un avancement d'échelon ne remet pas à zéro l'ancienneté de catégorie ou de classe.
        $dates = [];
        if (! $ancienne || $ancienne->categorie_id !== $nouvelle?->categorie_id) {
            $dates['date_effet_categorie'] = $mouvement->date_effet;
        }
        if (! $ancienne || $ancienne->classe_id !== $nouvelle?->classe_id) {
            $dates['date_effet_classe'] = $mouvement->date_effet;
        }
        if (! $ancienne || $ancienne->echelon_id !== $nouvelle?->echelon_id) {
            $dates['date_effet_echelon'] = $mouvement->date_effet;
        }
        if ($dates) {
            // Tout changement de position relance le délai d'avancement
            $dates['date_reference_avancement'] = $mouvement->date_effet;
        }

        $situation->update($dates + [
            'position_classification_id' => $nouvellePosition,
            'reference_acte' => $mouvement->reference_acte,
            'date_acte' => $mouvement->date_acte,
            'type_source' => TypeSourceSituation::ACTE->value,
            'enregistre_par' => auth()->id(),
            'enregistre_le' => now(),
        ]);
    }

    /**
     * Balayage global : applique tous les mouvements programmés dont la date
     * d'effet est passée. Exécuté par le Scheduler quotidien.
     */
    public function balayer(int $entrepriseId): int
    {
        $mouvements = MouvementCarriere::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('statut', StatutMouvement::PROGRAMME->value)
            ->whereNotNull('date_effet')
            ->where('date_effet', '<=', now())
            ->get();

        $compteur = 0;
        foreach ($mouvements as $m) {
            try {
                $this->appliquer($m);
                $compteur++;
            } catch (\Throwable $e) {
                \Log::error("Erreur application mouvement {$m->id} : " . $e->getMessage());
            }
        }

        return $compteur;
    }
}