<?php

namespace App\Domain\Interim\Enums;

// RG-TT-01 — état de l'agrément du ministre chargé du travail.
enum StatutAgrement: string
{
    case VALIDE = 'valide';
    case SUSPENDU = 'suspendu';
    case RETIRE = 'retire';

    public function libelle(): string
    {
        return match ($this) {
            self::VALIDE => 'Valide',
            self::SUSPENDU => 'Suspendu',
            self::RETIRE => 'Retiré',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
