<?php

namespace App\Domain\Interim\Enums;

/**
 * RG-INT-10 — Événements qui mettent fin à un intérim.
 */
enum CauseFinInterim: string
{
    case RETOUR_TITULAIRE = 'retour_titulaire';
    case RUPTURE_TITULAIRE = 'rupture_contrat_titulaire';
    case FIN_MOTIF = 'fin_motif';
    case DECISION = 'decision';

    public function libelle(): string
    {
        return match ($this) {
            self::RETOUR_TITULAIRE => 'Retour du titulaire',
            self::RUPTURE_TITULAIRE => 'Rupture du contrat du titulaire',
            self::FIN_MOTIF => 'Fin du motif',
            self::DECISION => 'Décision de fin (reclassement ou retour)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
