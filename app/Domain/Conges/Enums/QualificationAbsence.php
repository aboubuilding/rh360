<?php

namespace App\Domain\Conges\Enums;

enum QualificationAbsence: string
{
    case EN_ATTENTE     = 'en_attente';
    case JUSTIFIEE      = 'justifiee';
    case INJUSTIFIEE    = 'injustifiee';
    case AUTORISEE      = 'autorisee';
    case NON_AUTORISEE  = 'non_autorisee';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE    => 'En attente de qualification',
            self::JUSTIFIEE     => 'Justifiée',
            self::INJUSTIFIEE   => 'Injustifiée',
            self::AUTORISEE     => 'Autorisée',
            self::NON_AUTORISEE => 'Non autorisée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE    => 'secondary',
            self::JUSTIFIEE     => 'success',
            self::INJUSTIFIEE   => 'danger',
            self::AUTORISEE     => 'info',
            self::NON_AUTORISEE => 'danger',
        };
    }

    public function aImpactPaie(): bool
    {
        return in_array($this, [self::INJUSTIFIEE, self::NON_AUTORISEE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}