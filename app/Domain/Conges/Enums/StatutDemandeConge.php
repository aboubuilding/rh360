<?php

namespace App\Domain\Conges\Enums;

enum StatutDemandeConge: string
{
    case BROUILLON        = 'draft';
    case SOUMISE          = 'submitted';
    case ATTENTE_JUSTIFICATIF = 'awaiting_document';
    case AUTORISEE        = 'authorized';
    case PROGRAMMEE       = 'scheduled';
    case EN_COURS         = 'in_progress';
    case REPRISE_CONFIRMEE = 'resumed';
    case REFUSEE          = 'refused';
    case ANNULEE          = 'cancelled';
    // Justificatif non remis dans le délai : l'absence est traitée selon le paramétrage.
    case REQUALIFIEE      = 'requalified';

    public function libelle(): string
    {
        return match ($this) {
            self::BROUILLON         => 'Brouillon',
            self::SOUMISE           => 'Soumise',
            self::ATTENTE_JUSTIFICATIF => 'En attente de justificatif',
            self::AUTORISEE         => 'Autorisée',
            self::PROGRAMMEE        => 'Programmée',
            self::EN_COURS          => 'En cours',
            self::REPRISE_CONFIRMEE => 'Reprise confirmée',
            self::REFUSEE           => 'Refusée',
            self::ANNULEE           => 'Annulée',
            self::REQUALIFIEE       => 'Requalifiée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::BROUILLON         => 'secondary',
            self::SOUMISE           => 'info',
            self::ATTENTE_JUSTIFICATIF => 'warning',
            self::AUTORISEE         => 'primary',
            self::PROGRAMMEE        => 'primary',
            self::EN_COURS          => 'warning',
            self::REPRISE_CONFIRMEE => 'success',
            self::REFUSEE           => 'danger',
            self::ANNULEE           => 'danger',
            self::REQUALIFIEE       => 'dark',
        };
    }

    /** La demande réserve des jours dans le solde */
    public function reserveDesJours(): bool
    {
        return in_array($this, [
            self::AUTORISEE, self::PROGRAMMEE, self::EN_COURS,
        ], true);
    }

    /** La demande consomme des jours dans le solde */
    public function consommeDesJours(): bool
    {
        return $this === self::REPRISE_CONFIRMEE;
    }

    public function estModifiable(): bool
    {
        return in_array($this, [self::BROUILLON, self::SOUMISE], true);
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::REPRISE_CONFIRMEE, self::REFUSEE, self::ANNULEE, self::REQUALIFIEE], true);
    }

    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::BROUILLON         => [self::SOUMISE, self::ANNULEE],
            self::SOUMISE           => [self::ATTENTE_JUSTIFICATIF, self::AUTORISEE, self::REFUSEE, self::ANNULEE],
            // Pièce conforme : validée (absence de droit) ou rendue au valideur (absence soumise à accord).
            self::ATTENTE_JUSTIFICATIF => [self::AUTORISEE, self::SOUMISE, self::REQUALIFIEE, self::ANNULEE],
            self::AUTORISEE         => [self::PROGRAMMEE, self::ANNULEE],
            self::PROGRAMMEE        => [self::EN_COURS, self::ANNULEE],
            self::EN_COURS          => [self::REPRISE_CONFIRMEE, self::ANNULEE],
            self::REPRISE_CONFIRMEE => [],
            self::REFUSEE           => [],
            self::ANNULEE           => [],
            self::REQUALIFIEE       => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}