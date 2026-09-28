<?php

namespace App\Domain\Paie\Enums;

enum TraitementFiscal: string
{
    case IMPOSABLE                = 'taxable';
    case EXONERE                  = 'exempt';
    case EXONERATION_CONDITIONNELLE = 'conditional_exempt';
    case POURCENTAGE_IMPOSABLE    = 'partial_taxable';

    public function libelle(): string
    {
        return match ($this) {
            self::IMPOSABLE                 => 'Imposable',
            self::EXONERE                   => 'Exonéré',
            self::EXONERATION_CONDITIONNELLE => 'Exonération conditionnelle',
            self::POURCENTAGE_IMPOSABLE     => 'Partiellement imposable',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}