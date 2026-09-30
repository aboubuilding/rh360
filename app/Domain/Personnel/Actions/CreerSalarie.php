<?php

namespace App\Domain\Personnel\Actions;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\GenerateurNumeroMouvement;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Services\CalculateurCompletude;
use App\Domain\Personnel\Services\GenerateurMatricule;
use App\Domain\Personnel\Services\GenerateurNumeroEnregistrement;
use Illuminate\Support\Facades\DB;

class CreerSalarie
{
    public function __construct(
        private GenerateurMatricule $matricule,
        private GenerateurNumeroEnregistrement $numero,
        private CalculateurCompletude $completude,
        private GenerateurNumeroMouvement $numeroMouvement,
    ) {}

    /**
     * Crée le salarié et, selon les données de l'étape 5 (CDC §4 2.2) :
     * l'affectation initiale, la situation de carrière initiale et l'acte d'entrée en fonction.
     */
    public function executer(array $donnees): Salarie
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;

            // Générer matricule et n° enregistrement si absents
            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['matricule'] = $donnees['matricule'] ?? $this->matricule->generer($entrepriseId);
            $donnees['numero_enregistrement'] = $donnees['numero_enregistrement']
                ?? $this->numero->generer($entrepriseId);
            $donnees['statut_emploi'] = $donnees['statut_emploi'] ?? StatutEmploi::ACTIF->value;
            $donnees['statut_dossier'] = StatutDossier::INCOMPLET->value;
            $donnees['actif'] = true;
            $donnees['etat'] = 1;

            $salarie = Salarie::create($donnees);

            $dateEntree = $donnees['date_prise_service'] ?? $donnees['date_embauche'] ?? now();
            $aUneAffectation = ! empty($donnees['structure_id']) && ! empty($donnees['poste_id']);

            // Affectation initiale
            if ($aUneAffectation) {
                Affectation::create([
                    'salarie_id' => $salarie->id,
                    'structure_id' => $donnees['structure_id'],
                    'poste_id' => $donnees['poste_id'],
                    'date_debut' => $dateEntree,
                    'en_cours' => true,
                    'etat' => 1,
                ]);
            }

            // Situation de carrière initiale
            if (! empty($donnees['position_classification_id'])) {
                $dateEchelon = $donnees['date_effet_echelon'] ?? $dateEntree;

                SituationCarriere::create([
                    'entreprise_id' => $entrepriseId,
                    'salarie_id' => $salarie->id,
                    'position_classification_id' => $donnees['position_classification_id'],
                    'date_effet_categorie' => $dateEntree,
                    'date_effet_classe' => $dateEntree,
                    'date_effet_echelon' => $dateEchelon,
                    'date_reference_avancement' => $dateEchelon,
                    'type_source' => TypeSourceSituation::ACTE->value,
                    'statut_historique' => StatutHistorique::COMPLET->value,
                    'statut_fiabilite' => StatutFiabilite::CONFIRME->value,
                    'enregistre_par' => auth()->id(),
                    'enregistre_le' => now(),
                    'etat' => 1,
                ]);
            }

            // Acte d'entrée en fonction (déjà effectif)
            if ($aUneAffectation || ! empty($donnees['position_classification_id'])) {
                MouvementCarriere::create([
                    'entreprise_id' => $entrepriseId,
                    'numero_mouvement' => $this->numeroMouvement->generer($entrepriseId),
                    'salarie_id' => $salarie->id,
                    'type_mouvement' => TypeMouvement::AFFECTATION->value,
                    'statut' => StatutMouvement::EFFECTIF->value,
                    'date_proposition' => $dateEntree,
                    'date_decision' => $dateEntree,
                    'date_effet' => $dateEntree,
                    'structure_cible_id' => $donnees['structure_id'] ?? null,
                    'poste_cible_id' => $donnees['poste_id'] ?? null,
                    'position_classification_cible_id' => $donnees['position_classification_id'] ?? null,
                    'lieu_affectation_cible' => $donnees['lieu_affectation'] ?? null,
                    'motif' => 'Entrée en fonction',
                    'type_source' => TypeSourceSituation::ACTE->value,
                    'type_saisie_source' => 'normal',
                    'cree_par' => auth()->id(),
                    'valide_par' => auth()->id(),
                    'etat' => 1,
                ]);
            }

            // Recalculer la complétude
            $this->completude->mettreAJour($salarie->fresh());

            return $salarie->fresh(['affectations', 'membresFoyer']);
        });
    }
}
