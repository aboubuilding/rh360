<?php

namespace App\Domain\Conges\Enums;

/**
 * Ce à quoi s'applique le plafond : le salarié, ou chaque enfant concerné.
 */
enum BasePlafond: string
{
    case SALARIE = 'salarie';
    case ENFANT  = 'enfant';

    public function libelle(): string
    {
        return match ($this) {
            self::SALARIE => 'Par salarié',
            self::ENFANT  => 'Par enfant',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
