<?php

namespace App\Domain\Paie\Enums;

enum StatutPeriode: string
{
    case OUVERTE  = 'open';
    case CALCULEE = 'calculated';
    case VALIDEE  = 'validated';

    public function libelle(): string
    {
        return match ($this) {
            self::OUVERTE  => 'Ouverte',
            self::CALCULEE => 'Calculée',
            self::VALIDEE  => 'Validée (figée)',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::OUVERTE  => 'info',
            self::CALCULEE => 'primary',
            self::VALIDEE  => 'success',
        };
    }

    /** Une période validée est figée : aucun recalcul ni saisie */
    public function estFigee(): bool
    {
        return $this === self::VALIDEE;
    }

    public function peutCalculer(): bool
    {
        return in_array($this, [self::OUVERTE, self::CALCULEE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}