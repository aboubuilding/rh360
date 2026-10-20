<?php

namespace App\Domain\Interim\Enums;

enum StatutPret: string
{
    case PROJET = 'projet';
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case REFUSE_SALARIE = 'refuse_salarie';
    case ANNULE = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::PROJET => 'En projet',
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::REFUSE_SALARIE => 'Refusé par le salarié',
            self::ANNULE => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::PROJET => 'info',
            self::EN_COURS => 'primary',
            self::TERMINE => 'secondary',
            self::REFUSE_SALARIE => 'warning',
            self::ANNULE => 'dark',
        };
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::TERMINE, self::REFUSE_SALARIE, self::ANNULE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
