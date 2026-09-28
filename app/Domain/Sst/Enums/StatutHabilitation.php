<?php

namespace App\Domain\Sst\Enums;

enum StatutHabilitation: string
{
    case BROUILLON  = 'draft';
    case ACTIVE     = 'active';
    case EXPIREE    = 'expired';
    case SUSPENDUE  = 'suspended';
    case REVOQUEE   = 'revoked';

    public function libelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::ACTIVE    => 'Active',
            self::EXPIREE   => 'Expirée',
            self::SUSPENDUE => 'Suspendue',
            self::REVOQUEE  => 'Révoquée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::ACTIVE    => 'success',
            self::EXPIREE   => 'danger',
            self::SUSPENDUE => 'warning',
            self::REVOQUEE  => 'dark',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}