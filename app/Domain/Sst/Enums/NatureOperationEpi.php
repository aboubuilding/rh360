<?php

namespace App\Domain\Sst\Enums;

enum NatureOperationEpi: string
{
    case RESTITUTION   = 'restitution';
    case PERTE         = 'perte';
    case MISE_AU_REBUT = 'mise_au_rebut';
    case VERIFICATION  = 'verification';
    case REMPLACEMENT  = 'remplacement';

    public function libelle(): string
    {
        return match ($this) {
            self::RESTITUTION   => 'Restitution',
            self::PERTE         => 'Perte',
            self::MISE_AU_REBUT => 'Mise au rebut',
            self::VERIFICATION  => 'Vérification',
            self::REMPLACEMENT  => 'Remplacement',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}