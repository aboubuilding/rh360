<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Actions\CreerMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

/**
 * Propositions d'avancement de catégorie, ou d'activation du hors catégorie, pour les salariés
 * dont la fenêtre est ouverte (CCIT art. 33). L'échelon d'arrivée suit le paramètre.
 */
class PropositionsAvancementCategorie
{
    public function __construct(
        private CalculateurEligibilite $eligibilite,
        private ParametresAvancement $parametres,
        private CreerMouvement $creerMouvement,
    ) {}

    public function preparer(int $entrepriseId, int $jours = 90): int
    {
        $creees = 0;

        foreach ($this->eligibilite->fenetresCategorie($entrepriseId, $jours) as $fenetre) {
            if ($fenetre['statut'] === 'avant_ouverture' || ! $fenetre['categorie_cible_id']) {
                continue;
            }

            $salarie = Salarie::find($fenetre['salarie_id']);
            if (! $salarie || $salarie->statut_dossier !== StatutDossier::COMPLET) {
                continue;
            }

            $enCours = MouvementCarriere::where('salarie_id', $salarie->id)
                ->where('type_mouvement', TypeMouvement::PROMOTION->value)
                ->whereIn('statut', ['draft', 'proposed', 'to_check', 'checked', 'validated', 'scheduled'])
                ->exists();
            if ($enCours) {
                continue;
            }

            $cible = $this->positionArrivee($salarie, (int) $fenetre['categorie_cible_id']);
            if (! $cible) {
                continue;
            }

            $this->creerMouvement->executer([
                'salarie_id' => $salarie->id,
                'type_mouvement' => TypeMouvement::PROMOTION->value,
                'sous_type_mouvement' => ControleAvancement::AVANCEMENT_CATEGORIE,
                'date_eligibilite' => $fenetre['ouverture'],
                'position_classification_depart_id' => $fenetre['position_actuelle_id'],
                'position_classification_cible_id' => $cible->id,
                'motif' => ($fenetre['hors_categorie'] ? 'Activation du hors catégorie' : 'Avancement de catégorie')
                    .' : fenêtre du '.Carbon::parse($fenetre['ouverture'])->format('d/m/Y')
                    .' au '.Carbon::parse($fenetre['limite'])->format('d/m/Y'),
            ]);
            $creees++;
        }

        return $creees;
    }

    /**
     * Échelon d'arrivée dans la catégorie cible : échelon A (le plus bas), ou premier échelon dont
     * le minimum est au moins égal au salaire de base actuel.
     */
    public function positionArrivee(Salarie $salarie, int $categorieId): ?PositionClassification
    {
        $this->parametres->pourEntreprise($salarie->entreprise_id);

        $positions = PositionClassification::where('categorie_id', $categorieId)
            ->with('echelon')->get()
            ->sortBy(fn ($p) => $p->echelon?->ordre ?? 0)
            ->values();

        if ($this->parametres->get('echelon_arrivee') !== 'salaire_immediatement_superieur') {
            return $positions->first();
        }

        $salaire = (int) ElementPaieSalarie::where('salarie_id', $salarie->id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');

        return $positions->first(fn ($p) => (int) $p->salaire_minimum >= $salaire) ?? $positions->last();
    }
}
