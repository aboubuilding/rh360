<?php

namespace App\Domain\Interim\Services;

use App\Domain\Classification\Models\CategorieClassification;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Résultat des contrôles d'un intérim interne : erreurs et valeurs calculées.
 */
class ControleInterim
{
    /** @var array<string, list<string>> */
    public array $erreurs = [];

    public ?CategorieClassification $categorieOccupee = null;

    public ?CategorieClassification $categorieOrigine = null;

    public ?Carbon $plafondNormal = null;

    public ?Carbon $dateLimite = null;

    public int $joursDejaEffectues = 0;

    public function erreur(string $champ, string $message): void
    {
        $this->erreurs[$champ][] = $message;
    }

    public function estValide(): bool
    {
        return $this->erreurs === [];
    }

    public function exigerValide(): self
    {
        if (! $this->estValide()) {
            throw ValidationException::withMessages($this->erreurs);
        }

        return $this;
    }
}
