<?php

namespace App\Domain\Contrats\Enums;

/**
 * Circuit du contrat : brouillon → soumis → validé → signé / référencé.
 * Un retour au brouillon ou une annulation exige un motif ; un contrat signé
 * n'est plus modifiable et évolue par avenant.
 */
enum StatutContrat: string
{
    case BROUILLON = 'draft';
    case SOUMIS    = 'submitted';
    case VALIDE    = 'validated';
    case SIGNE     = 'signed';
    case ANNULE    = 'cancelled';

    public function libelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMIS    => 'Soumis',
            self::VALIDE    => 'Validé',
            self::SIGNE     => 'Signé / référencé',
            self::ANNULE    => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::SOUMIS    => 'info',
            self::VALIDE    => 'primary',
            self::SIGNE     => 'success',
            self::ANNULE    => 'danger',
        };
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::SIGNE, self::ANNULE], true);
    }

    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::BROUILLON => [self::SOUMIS, self::ANNULE],
            self::SOUMIS    => [self::VALIDE, self::BROUILLON, self::ANNULE],
            self::VALIDE    => [self::SIGNE, self::BROUILLON, self::ANNULE],
            self::SIGNE     => [],
            self::ANNULE    => [],
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
