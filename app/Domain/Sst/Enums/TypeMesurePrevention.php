<?php

namespace App\Domain\Sst\Enums;

enum TypeMesurePrevention: string
{
    case SUPPRESSION    = 'suppression';
    case SUBSTITUTION   = 'substitution';
    case PROTECTION_COLLECTIVE = 'protection_collective';
    case PROTECTION_INDIVIDUELLE = 'protection_individuelle';
    case FORMATION      = 'formation';
    case SIGNALISATION  = 'signalisation';
    case PROCEDURE      = 'procedure';
    case AUTRE          = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::SUPPRESSION                => 'Suppression du danger',
            self::SUBSTITUTION               => 'Substitution',
            self::PROTECTION_COLLECTIVE      => 'Protection collective',
            self::PROTECTION_INDIVIDUELLE    => 'Protection individuelle',
            self::FORMATION                  => 'Formation / information',
            self::SIGNALISATION              => 'Signalisation',
            self::PROCEDURE                  => 'Procédure',
            self::AUTRE                      => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}