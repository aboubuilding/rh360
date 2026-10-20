<?php

namespace App\Domain\Contrats\Enums;

/**
 * RG-REM-01 — Motifs de recours au CDD de remplacement (Code du travail, art. 49 a).
 */
enum MotifRecoursCdd: string
{
    case REMPLACEMENT_ABSENT = 'remplacement_absent';
    case REMPLACEMENT_SUSPENDU = 'remplacement_suspendu';
    case ATTENTE_CDI = 'attente_cdi';
    case AUTRE = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::REMPLACEMENT_ABSENT => 'Remplacement d\'un salarié absent',
            self::REMPLACEMENT_SUSPENDU => 'Remplacement d\'un salarié dont le contrat est suspendu',
            self::ATTENTE_CDI => 'Attente de l\'arrivée d\'un salarié en CDI',
            self::AUTRE => 'Autre motif',
        };
    }

    // Le titulaire remplacé est obligatoire sur le contrat.
    public function estRemplacement(): bool
    {
        return in_array($this, [self::REMPLACEMENT_ABSENT, self::REMPLACEMENT_SUSPENDU], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
