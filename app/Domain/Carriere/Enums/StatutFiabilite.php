<?php

namespace App\Domain\Carriere\Enums;

enum StatutFiabilite: string
{
    case A_CONFIRMER = 'to_confirm';
    case CONFIRME    = 'confirmed';
    case LITIGIEUX   = 'disputed';

    public function libelle(): string
    {
        return match ($this) {
            self::A_CONFIRMER => 'À confirmer',
            self::CONFIRME    => 'Confirmé',
            self::LITIGIEUX   => 'Litigieux',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::A_CONFIRMER => 'warning',
            self::CONFIRME    => 'success',
            self::LITIGIEUX   => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}