<?php

namespace App\Domain\Conges\Enums;

enum ImpactCongeAnnuel: string
{
    case AUCUNE           = 'aucune';
    case SUSPEND          = 'suspend';
    case DIMINUE          = 'diminue';

    public function libelle(): string
    {
        return match ($this) {
            self::AUCUNE  => 'Aucun impact',
            self::SUSPEND => 'Suspend l\'acquisition',
            self::DIMINUE => 'Diminue le droit annuel',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}