<?php

namespace Database\Seeders;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Services\InstallateurCatalogueDemandes;
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
                'code' => 'ABS-NJ',
                'famille' => FamilleDemande::AUTRE->value,
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
            [
                // RG-TT-05 — sert au refus des missions d'intérim remplaçant des grévistes.
                // Traitement salarial et impacts à vérifier par les RH.
                'code' => 'GREVE',
                'famille' => FamilleDemande::AUTRE->value,
                'nom' => 'Grève',
                'categorie' => CategorieTypeConge::ABSENCE->value,
                'unite' => UniteConge::JOUR_CALENDAIRE->value,
                'droit_annuel' => 0,
                'remunere' => false,
                'justificatif_requis' => false,
                'traitement_salarial' => TraitementSalarial::NON_REMUNERE->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
                'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
                'autorisation_prealable_requise' => false,
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

        // Types et événements de permission tirés des textes : alignés pour une nouvelle installation.
        $resultat = app(InstallateurCatalogueDemandes::class)->installer($entrepriseId, aligner: true);

        $this->command->info((count($types) + $resultat['crees']) . ' types de congés créés.');
    }
}