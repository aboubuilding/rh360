<?php

namespace App\Domain\Performance\Enums;

enum StatutCampagne: string
{
    case PREPARATION = 'preparation';
    case EN_COURS    = 'en_cours';
    case CLOTUREE    = 'cloturee';
    case ARCHIVEE    = 'archivee';

    public function libelle(): string
    {
        return match ($this) {
            self::PREPARATION => 'Préparation',
            self::EN_COURS    => 'En cours',
            self::CLOTUREE    => 'Clôturée',
            self::ARCHIVEE    => 'Archivée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::PREPARATION => 'secondary',
            self::EN_COURS    => 'primary',
            self::CLOTUREE    => 'success',
            self::ARCHIVEE    => 'dark',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}