<?php

namespace App\Domain\Paie\Enums;

/**
 * Cas dans lesquels une retenue sur salaire est permise (Code art. 173 ; CCIT art. 35). Toute
 * autre retenue est nulle (Code art. 175), et une sanction pécuniaire est interdite (Code art. 120).
 */
enum TypeRetenue: string
{
    case OBLIGATOIRE = 'obligatoire';
    case ACOMPTE = 'acompte';
    case CESSION = 'cession';
    case SAISIE_ATTRIBUTION = 'saisie_attribution';
    case MUTUELLE = 'mutuelle';
    case COTISATION_SYNDICALE = 'cotisation_syndicale';
    case CONSIGNATION = 'consignation';

    public function libelle(): string
    {
        return match ($this) {
            self::OBLIGATOIRE => 'Prélèvement obligatoire (cotisations, impôt)',
            self::ACOMPTE => 'Acompte sur le travail en cours',
            self::CESSION => 'Cession volontaire (remboursement d\'avance ou de prêt)',
            self::SAISIE_ATTRIBUTION => 'Saisie-attribution',
            self::MUTUELLE => 'Cotisation de société de secours mutuel',
            self::COTISATION_SYNDICALE => 'Cotisation syndicale (avec l\'accord du salarié)',
            self::CONSIGNATION => 'Consignation prévue par la convention ou le contrat',
        };
    }

    // Retenues plafonnées par la quotité cessible ou saisissable (Code art. 174).
    public function plafonneeParQuotite(): bool
    {
        return in_array($this, [self::CESSION, self::SAISIE_ATTRIBUTION], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
