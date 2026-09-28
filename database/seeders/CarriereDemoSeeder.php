<?php

namespace Database\Seeders;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Services\GenerateurNumeroMouvement;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class CarriereDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();
        $generateur = app(GenerateurNumeroMouvement::class);

        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        $positions = PositionClassification::all();
        $structures = Structure::where('entreprise_id', $entrepriseId)->get();
        $postes = Poste::where('entreprise_id', $entrepriseId)->get();

        if ($positions->isEmpty() || $structures->isEmpty() || $postes->isEmpty()) {
            $this->command->warn('Données de classification / organisation manquantes.');
            return;
        }

        $compteurSituations = 0;
        $compteurMouvements = 0;

        foreach ($salaries as $i => $salarie) {
            // Éviter les doublons
            if (SituationCarriere::where('salarie_id', $salarie->id)->exists()) {
                continue;
            }

            // Choisir une position en fonction de l'index (variété)
            $position = $positions[$i % $positions->count()];

            // Créer la situation de carrière courante
            SituationCarriere::create([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'position_classification_id' => $position->id,
                'date_effet_categorie' => $salarie->date_embauche,
                'date_effet_classe' => $salarie->date_embauche,
                'date_effet_echelon' => $salarie->date_embauche,
                'date_reference_avancement' => $salarie->date_embauche,
                'type_source' => TypeSourceSituation::MANUEL->value,
                'statut_historique' => StatutHistorique::COMPLET->value,
                'statut_fiabilite' => StatutFiabilite::CONFIRME->value,
                'enregistre_par' => $superAdmin?->id ?? 1,
                'enregistre_le' => now(),
                'etat' => 1,
            ]);
            $compteurSituations++;

            // Créer 1 ou 2 mouvements historiques (effectifs) pour les 3 premiers
            if ($i < 3) {
                // Mouvement d'affectation initiale
                $structure = $structures->random();
                $poste = $postes->where('structure_id', $structure->id)->first() ?? $postes->first();

                MouvementCarriere::create([
                    'entreprise_id' => $entrepriseId,
                    'numero_mouvement' => $generateur->generer($entrepriseId),
                    'salarie_id' => $salarie->id,
                    'type_mouvement' => TypeMouvement::AFFECTATION->value,
                    'statut' => StatutMouvement::EFFECTIF->value,
                    'date_proposition' => $salarie->date_embauche,
                    'date_decision' => $salarie->date_embauche,
                    'date_effet' => $salarie->date_embauche,
                    'structure_cible_id' => $structure->id,
                    'poste_cible_id' => $poste->id,
                    'lieu_affectation_cible' => $structure->localisation,
                    'motif' => 'Affectation initiale à l\'embauche',
                    'type_source' => TypeSourceSituation::MANUEL->value,
                    'cree_par' => $superAdmin?->id ?? 1,
                    'valide_par' => $superAdmin?->id ?? 1,
                    'etat' => 1,
                ]);
                $compteurMouvements++;

                // Pour le 1er salarié uniquement : ajouter un mouvement validé (non appliqué)
                if ($i === 0 && $positions->count() > 1) {
                    $nouvellePosition = $positions->where('id', '!=', $position->id)->first();

                    MouvementCarriere::create([
                        'entreprise_id' => $entrepriseId,
                        'numero_mouvement' => $generateur->generer($entrepriseId),
                        'salarie_id' => $salarie->id,
                        'type_mouvement' => TypeMouvement::AVANCEMENT->value,
                        'statut' => StatutMouvement::VALIDE->value,
                        'date_proposition' => now()->subDays(15),
                        'date_eligibilite' => now()->addDays(10),
                        'date_decision' => now()->subDays(5),
                        'position_classification_depart_id' => $position->id,
                        'position_classification_cible_id' => $nouvellePosition?->id,
                        'motif' => 'Avancement proposé après 24 mois dans l\'échelon',
                        'type_source' => TypeSourceSituation::MANUEL->value,
                        'cree_par' => $superAdmin?->id ?? 1,
                        'valide_par' => $superAdmin?->id ?? 1,
                        'etat' => 1,
                    ]);
                    $compteurMouvements++;
                }

                // Pour le 2ᵉ salarié : intérim en cours
                if ($i === 1) {
                    $structure = $structures->random();
                    $poste = $postes->where('structure_id', $structure->id)->first() ?? $postes->first();

                    MouvementCarriere::create([
                        'entreprise_id' => $entrepriseId,
                        'numero_mouvement' => $generateur->generer($entrepriseId),
                        'salarie_id' => $salarie->id,
                        'type_mouvement' => TypeMouvement::INTERIM->value,
                        'statut' => StatutMouvement::EFFECTIF->value,
                        'date_proposition' => now()->subDays(20),
                        'date_decision' => now()->subDays(15),
                        'date_effet' => now()->subDays(10),
                        'date_fin_prevue' => now()->addMonths(2),
                        'structure_cible_id' => $structure->id,
                        'poste_cible_id' => $poste->id,
                        'lieu_affectation_cible' => $structure->localisation,
                        'motif' => 'Remplacement temporaire d\'un collègue en congé',
                        'type_source' => TypeSourceSituation::MANUEL->value,
                        'cree_par' => $superAdmin?->id ?? 1,
                        'valide_par' => $superAdmin?->id ?? 1,
                        'etat' => 1,
                    ]);
                    $compteurMouvements++;
                }
            }
        }

        $this->command->info("{$compteurSituations} situation(s) et {$compteurMouvements} mouvement(s) de carrière créés.");
    }
}