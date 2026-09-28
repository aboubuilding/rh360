<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
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

            // 2. Appliquer les changements selon le type
            if ($mouvement->estAffectation() || $mouvement->estTemporaire()) {
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
            'structure_id' => $mouvement->structure_cible_id,
            'poste_id' => $mouvement->poste_cible_id,
            'date_debut' => $mouvement->date_effet,
            'en_cours' => true,
            'etat' => 1,
        ]);

        // Mettre à jour le lieu d'affectation sur la fiche salarié
        $mouvement->salarie->update([
            'lieu_affectation' => $mouvement->lieu_affectation_cible,
        ]);
    }

    /**
     * Met à jour la situation de carrière (classification + dates).
     */
    private function appliquerEvolutionCarriere(MouvementCarriere $mouvement): void
    {
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

        $nouvellePosition = $mouvement->position_classification_cible_id;
        $positionActuelle = $situation->position_classification_id;

        $situation->update([
            'position_classification_id' => $nouvellePosition,
            'date_effet_categorie' => $mouvement->date_effet,
            'date_effet_classe' => $mouvement->date_effet,
            'date_effet_echelon' => $mouvement->date_effet,
            'date_reference_avancement' => $mouvement->date_effet,
            'reference_acte' => $mouvement->reference_acte,
            'date_acte' => $mouvement->date_acte,
            'type_source' => TypeSourceSituation::ACTE->value,
            'enregistre_par' => auth()->id(),
            'enregistre_le' => now(),
        ]);

        // Si la position change de catégorie/classe, ajuster les dates
        if ($nouvellePosition !== $positionActuelle) {
            $ancienne = \App\Domain\Classification\Models\PositionClassification::find($positionActuelle);
            $nouvelle = \App\Domain\Classification\Models\PositionClassification::find($nouvellePosition);

            if ($ancienne && $nouvelle) {
                if ($ancienne->categorie_id !== $nouvelle->categorie_id) {
                    $situation->update(['date_effet_categorie' => $mouvement->date_effet]);
                }
                if ($ancienne->classe_id !== $nouvelle->classe_id) {
                    $situation->update(['date_effet_classe' => $mouvement->date_effet]);
                }
                if ($ancienne->echelon_id !== $nouvelle->echelon_id) {
                    $situation->update(['date_effet_echelon' => $mouvement->date_effet]);
                }
            }
        }
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