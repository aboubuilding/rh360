<?php

namespace App\Domain\Conges\Enums;

enum ImpactAnciennete: string
{
    case MAINTENUE = 'maintenue';
    case SUSPENDUE = 'suspendue';

    public function libelle(): string
    {
        return match ($this) {
            self::MAINTENUE => 'Ancienneté maintenue',
            self::SUSPENDUE => 'Ancienneté suspendue',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}