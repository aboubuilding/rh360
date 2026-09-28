<?php

namespace App\Domain\Sst\Services;

use App\Domain\Sst\Enums\NiveauRisque;

class CalculateurScoreRisque
{
    /**
     * Calcule le score (gravité × probabilité) et détermine le niveau.
     *
     * @return array{score: int, niveau: NiveauRisque}
     */
    public function calculer(int $gravite, int $probabilite): array
    {
        if ($gravite < 1 || $gravite > 5) {
            throw new \InvalidArgumentException('La gravité doit être comprise entre 1 et 5.');
        }
        if ($probabilite < 1 || $probabilite > 5) {
            throw new \InvalidArgumentException('La probabilité doit être comprise entre 1 et 5.');
        }

        $score = $gravite * $probabilite;

        return [
            'score' => $score,
            'niveau' => NiveauRisque::depuisScore($score),
        ];
    }

    /**
     * Suggère la prochaine date de revue selon le niveau.
     */
    public function suggererProchaineRevue(NiveauRisque $niveau): \Carbon\Carbon
    {
        return match ($niveau) {
            NiveauRisque::CRITIQUE => now()->addMonths(1),
            NiveauRisque::ELEVE    => now()->addMonths(3),
            NiveauRisque::MOYEN    => now()->addMonths(6),
            NiveauRisque::FAIBLE   => now()->addYear(),
        };
    }
}