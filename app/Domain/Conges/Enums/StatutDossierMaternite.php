<?php

namespace App\Domain\Conges\Enums;

enum StatutDossierMaternite: string
{
    case DECLAREE       = 'declaree';
    case EN_COURS       = 'en_cours';
    case CONGE_EN_COURS = 'conge_en_cours';
    case REPRISE        = 'reprise';
    case CLOTURE        = 'cloture';

    public function libelle(): string
    {
        return match ($this) {
            self::DECLAREE       => 'Déclarée',
            self::EN_COURS       => 'En cours de suivi',
            self::CONGE_EN_COURS => 'Congé en cours',
            self::REPRISE        => 'Reprise effective',
            self::CLOTURE        => 'Clôturé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::DECLAREE       => 'info',
            self::EN_COURS       => 'primary',
            self::CONGE_EN_COURS => 'warning',
            self::REPRISE        => 'success',
            self::CLOTURE        => 'secondary',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}