<?php

namespace App\Domain\Interim\Enums;

/**
 * Motif d'un intérim interne (CCIT art. 13).
 */
enum MotifInterim: string
{
    case MALADIE_ACCIDENT = 'maladie_accident';
    case CONGE_ANNUEL = 'conge_annuel';
    case CONGE_MATERNITE = 'conge_maternite';
    case DETENTION_PREVENTIVE = 'detention_preventive';
    case AT_MP = 'at_mp';
    case POSTE_VACANT = 'poste_vacant';
    case REORGANISATION = 'reorganisation';
    case AUTRE = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::MALADIE_ACCIDENT => 'Maladie ou accident du titulaire',
            self::CONGE_ANNUEL => 'Congé annuel du titulaire',
            self::CONGE_MATERNITE => 'Congé de maternité de la titulaire',
            self::DETENTION_PREVENTIVE => 'Détention préventive du titulaire',
            self::AT_MP => 'Accident du travail ou maladie professionnelle du titulaire',
            self::POSTE_VACANT => 'Poste vacant',
            self::REORGANISATION => 'Réorganisation',
            self::AUTRE => 'Autre motif',
        };
    }

    // RG-INT-04 — Seuls ces motifs permettent de prolonger au-delà de deux mois.
    public function estEligibleProlongation(): bool
    {
        return $this->cleDelaiLegal() !== null;
    }

    // RG-INT-05 — Nature du délai légal du motif (voir CalculateurDelaisInterim::delaiLegal).
    public function cleDelaiLegal(): ?string
    {
        return match ($this) {
            self::MALADIE_ACCIDENT => 'maladie',
            self::DETENTION_PREVENTIVE => 'detention_preventive',
            self::CONGE_ANNUEL, self::CONGE_MATERNITE => 'conges',
            self::AT_MP => 'at_mp',
            default => null,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
