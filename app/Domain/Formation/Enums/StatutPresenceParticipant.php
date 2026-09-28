<?php

namespace App\Domain\Formation\Enums;

enum StatutPresenceParticipant: string
{
    case INSCRIT    = 'inscrit';
    case PRESENT    = 'present';
    case ABSENT     = 'absent';
    case EXCUSE     = 'excuse';
    case ABANDON    = 'abandon';

    public function libelle(): string
    {
        return match ($this) {
            self::INSCRIT => 'Inscrit',
            self::PRESENT => 'Présent',
            self::ABSENT  => 'Absent',
            self::EXCUSE  => 'Excusé',
            self::ABANDON => 'Abandon',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::INSCRIT => 'secondary',
            self::PRESENT => 'success',
            self::ABSENT  => 'danger',
            self::EXCUSE  => 'warning',
            self::ABANDON => 'dark',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}