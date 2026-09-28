<?php

namespace App\Domain\Formation\Enums;

enum ModaliteFormation: string
{
    case PRESENTIEL = 'presentiel';
    case DISTANCIEL = 'distanciel';
    case MIXTE      = 'mixte';
    case TERRAIN    = 'terrain';
    case E_LEARNING = 'e_learning';

    public function libelle(): string
    {
        return match ($this) {
            self::PRESENTIEL => 'Présentiel',
            self::DISTANCIEL => 'Distanciel',
            self::MIXTE      => 'Mixte',
            self::TERRAIN    => 'Formation terrain',
            self::E_LEARNING => 'E-learning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}