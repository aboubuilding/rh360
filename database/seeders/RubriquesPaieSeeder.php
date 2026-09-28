<?php

namespace Database\Seeders;

use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Models\RubriquePaie;
use Illuminate\Database\Seeder;

class RubriquesPaieSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $rubriques = [
            // ===== GAINS =====
            [
                'code' => 'SAL-BASE',
                'nom' => 'Salaire de base',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'taux' => 0,
                'montant_defaut' => 0,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'PRIME-TRANSP',
                'nom' => 'Prime de transport',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'montant_defaut' => 15000,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'pourcentage_imposable' => 0,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
            [
                'code' => 'PRIME-LOGT',
                'nom' => 'Prime de logement',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'montant_defaut' => 30000,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'PRIME-ANC',
                'nom' => 'Prime d\'ancienneté',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::TAUX_POURCENT->value,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'HS',
                'nom' => 'Heures supplémentaires',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::QUANTITE_TAUX->value,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'RAPPEL',
                'nom' => 'Rappel d\'avancement',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::PONCTUELLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'PRIME-REND',
                'nom' => 'Prime de rendement',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],
            [
                'code' => 'INDEMNITE-CONGES',
                'nom' => 'Indemnité de congés payés',
                'nature' => NatureRubrique::GAIN->value,
                'recurrence' => RecurrenceRubrique::PONCTUELLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => true,
                'traitement_fiscal' => TraitementFiscal::IMPOSABLE->value,
                'pourcentage_imposable' => 100,
                'soumis_cotisation' => true,
                'actif' => true,
            ],

            // ===== RETENUES =====
            [
                'code' => 'AVANCE',
                'nom' => 'Avance sur salaire',
                'nature' => NatureRubrique::RETENUE->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
            [
                'code' => 'PRET',
                'nom' => 'Remboursement de prêt',
                'nature' => NatureRubrique::RETENUE->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
            [
                'code' => 'ABS-NJ',
                'nom' => 'Retenue absence non justifiée',
                'nature' => NatureRubrique::RETENUE->value,
                'recurrence' => RecurrenceRubrique::VARIABLE->value,
                'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],

            // ===== COTISATIONS =====
            [
                'code' => 'CNSS',
                'nom' => 'CNSS (part salariale)',
                'nature' => NatureRubrique::COTISATION->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::TAUX_POURCENT->value,
                'taux' => 4.0,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
            [
                'code' => 'AMU',
                'nom' => 'AMU (part salariale)',
                'nature' => NatureRubrique::COTISATION->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::TAUX_POURCENT->value,
                'taux' => 5.0,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
            [
                'code' => 'IRPP',
                'nom' => 'Impôt sur le revenu (IRPP)',
                'nature' => NatureRubrique::RETENUE->value,
                'recurrence' => RecurrenceRubrique::FIXE->value,
                'mode_calcul' => ModeCalculRubrique::FORMULE->value,
                'imposable' => false,
                'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                'soumis_cotisation' => false,
                'actif' => true,
            ],
        ];

        foreach ($rubriques as $r) {
            RubriquePaie::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $r['code']],
                array_merge($r, [
                    'entreprise_id' => $entrepriseId,
                    'etat' => 1,
                ])
            );
        }

        $this->command->info(count($rubriques) . ' rubriques de paie créées.');
    }
}