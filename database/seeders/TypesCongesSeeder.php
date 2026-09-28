<?php

namespace Database\Seeders;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypesCongesSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $types = [
            [
                'code' => 'CA',
                'nom' => 'Congé annuel payé',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 30,
                'remunere' => true,
                'justificatif_requis' => false,
                'reference_legale' => 'Code du travail',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => true,
            ],
            [
                'code' => 'MAL',
                'nom' => 'Congé maladie',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => true,
                'reference_legale' => 'Code du travail',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'delai_justification_jours' => 5,
                'autorisation_prealable_requise' => false,
            ],
            [
                'code' => 'MAT',
                'nom' => 'Congé de maternité',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => true,
                'reference_legale' => 'Code du travail — 14 semaines',
                'duree_max' => 98,
                'portee_duree_max' => 'par événement',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => true,
            ],
            [
                'code' => 'PAT',
                'nom' => 'Congé de paternité',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => true,
                'reference_legale' => 'Code du travail — 10 jours',
                'duree_max' => 10,
                'portee_duree_max' => 'par événement',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => true,
            ],
            [
                'code' => 'DECES',
                'nom' => 'Congé pour décès d\'un proche',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => true,
                'reference_legale' => 'Convention collective',
                'duree_max' => 5,
                'portee_duree_max' => 'par événement',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => false,
            ],
            [
                'code' => 'MARIAGE',
                'nom' => 'Congé pour mariage',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => true,
                'reference_legale' => 'Convention collective',
                'duree_max' => 4,
                'portee_duree_max' => 'par événement',
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => true,
            ],
            [
                'code' => 'PERM-EXC',
                'nom' => 'Permission exceptionnelle',
                'categorie' => CategorieTypeConge::PERMISSION->value,
                'unite' => UniteConge::HEURE->value,
                'droit_annuel' => 0,
                'remunere' => true,
                'justificatif_requis' => false,
                'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => true,
            ],
            [
                'code' => 'ABS-NJ',
                'nom' => 'Absence non justifiée',
                'categorie' => CategorieTypeConge::ABSENCE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => false,
                'justificatif_requis' => false,
                'traitement_salarial' => TraitementSalarial::NON_REMUNERE->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'impact_anciennete' => ImpactAnciennete::SUSPENDUE->value,
                'autorisation_prealable_requise' => false,
            ],
            [
                'code' => 'SANS-SOLDE',
                'nom' => 'Congé sans solde',
                'categorie' => CategorieTypeConge::CONGE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => false,
                'justificatif_requis' => false,
                'traitement_salarial' => TraitementSalarial::NON_REMUNERE->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'impact_anciennete' => ImpactAnciennete::SUSPENDUE->value,
                'autorisation_prealable_requise' => true,
            ],
        ];

        foreach ($types as $type) {
            TypeConge::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $type['code']],
                array_merge($type, [
                    'entreprise_id' => $entrepriseId,
                    'actif' => true,
                    'etat' => 1,
                ])
            );
        }

        $this->command->info(count($types) . ' types de congés créés.');
    }
}