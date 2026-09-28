<?php

namespace App\Domain\Conges\Enums;

enum CategorieTypeConge: string
{
    case CONGE      = 'conge';
    case PERMISSION = 'permission';
    case ABSENCE    = 'absence';

    public function libelle(): string
    {
        return match ($this) {
            self::CONGE      => 'Congé',
            self::PERMISSION => 'Permission',
            self::ABSENCE    => 'Absence',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}