<?php

namespace App\Domain\Interim\Enums;

enum StatutInterim: string
{
    case EN_COURS = 'en_cours';
    case PROLONGE = 'prolonge';
    case A_REGULARISER = 'a_regulariser';
    case CLOS = 'clos';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::PROLONGE => 'Prolongé',
            self::A_REGULARISER => 'À régulariser',
            self::CLOS => 'Clos',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_COURS => 'info',
            self::PROLONGE => 'primary',
            self::A_REGULARISER => 'danger',
            self::CLOS => 'secondary',
        };
    }

    public function estActif(): bool
    {
        return $this !== self::CLOS;
    }

    /** @return list<string> */
    public static function valeursActives(): array
    {
        return [self::EN_COURS->value, self::PROLONGE->value, self::A_REGULARISER->value];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
