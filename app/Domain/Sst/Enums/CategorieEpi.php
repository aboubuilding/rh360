<?php

namespace App\Domain\Sst\Enums;

enum CategorieEpi: string
{
    case TETE         = 'tete';
    case MAINS        = 'mains';
    case PIEDS        = 'pieds';
    case CORPS        = 'corps';
    case YEUX         = 'yeux';
    case OREILLES     = 'oreilles';
    case RESPIRATION  = 'respiration';
    case CHUTE        = 'chute';

    public function libelle(): string
    {
        return match ($this) {
            self::TETE         => 'Protection de la tête',
            self::MAINS        => 'Protection des mains',
            self::PIEDS        => 'Protection des pieds',
            self::CORPS        => 'Protection du corps',
            self::YEUX         => 'Protection des yeux',
            self::OREILLES     => 'Protection auditive',
            self::RESPIRATION  => 'Protection respiratoire',
            self::CHUTE        => 'Protection anti-chute',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}