<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Paie\Enums\TypeRetenue;
use App\Domain\Paie\Models\RubriquePaie;

/**
 * Rubriques des retenues autorisées qui manquent au catalogue de départ : saisie-attribution,
 * cotisation syndicale, mutuelle (Code art. 173 ; CCIT art. 35) — idempotent.
 */
class InstallateurRubriquesRetenues
{
    public const RUBRIQUES = [
        'SAISIE-ATT' => ['Saisie-attribution sur salaire', TypeRetenue::SAISIE_ATTRIBUTION],
        'COT-SYND' => ['Cotisation syndicale', TypeRetenue::COTISATION_SYNDICALE],
        'MUTUELLE' => ['Cotisation de société de secours mutuel', TypeRetenue::MUTUELLE],
    ];

    public static function installer(int $entrepriseId): void
    {
        foreach (self::RUBRIQUES as $code => [$nom, $type]) {
            RubriquePaie::withoutGlobalScopes()->firstOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $code],
                [
                    'nom' => $nom,
                    'nature' => NatureRubrique::RETENUE->value,
                    'type_retenue' => $type->value,
                    'recurrence' => RecurrenceRubrique::VARIABLE->value,
                    'mode_calcul' => ModeCalculRubrique::MONTANT_FIXE->value,
                    'imposable' => false,
                    'traitement_fiscal' => TraitementFiscal::EXONERE->value,
                    'pourcentage_imposable' => 0,
                    'soumis_cotisation' => false,
                    'est_frais' => false,
                    'actif' => true,
                    'etat' => 1,
                ],
            );
        }
    }
}
