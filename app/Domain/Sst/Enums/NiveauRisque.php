<?php

namespace App\Domain\Sst\Enums;

enum NiveauRisque: string
{
    case FAIBLE    = 'faible';
    case MOYEN     = 'moyen';
    case ELEVE     = 'eleve';
    case CRITIQUE  = 'critique';

    public function libelle(): string
    {
        return match ($this) {
            self::FAIBLE   => 'Faible',
            self::MOYEN    => 'Moyen',
            self::ELEVE    => 'Élevé',
            self::CRITIQUE => 'Critique',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::FAIBLE   => 'success',
            self::MOYEN    => 'warning',
            self::ELEVE    => 'danger',
            self::CRITIQUE => 'dark',
        };
    }

    /**
     * Détermine le niveau selon le score (gravité × probabilité).
     */
    public static function depuisScore(int $score): self
    {
        return match (true) {
            $score <= 4  => self::FAIBLE,
            $score <= 9  => self::MOYEN,
            $score <= 16 => self::ELEVE,
            default      => self::CRITIQUE,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}