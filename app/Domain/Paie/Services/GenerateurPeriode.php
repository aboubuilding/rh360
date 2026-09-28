<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;

class GenerateurPeriode
{
    /**
     * Ouvre une nouvelle période de paie pour un mois donné.
     * Refuse si la période existe déjà.
     */
    public function ouvrir(int $entrepriseId, int $annee, int $mois): PeriodePaie
    {
        if ($mois < 1 || $mois > 12) {
            throw new \DomainException('Le mois doit être compris entre 1 et 12.');
        }

        $existante = PeriodePaie::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('annee', $annee)
            ->where('mois', $mois)
            ->first();

        if ($existante) {
            throw new \DomainException("La période {$existante->libelle} existe déjà.");
        }

        return PeriodePaie::create([
            'entreprise_id' => $entrepriseId,
            'annee' => $annee,
            'mois' => $mois,
            'statut' => StatutPeriode::OUVERTE->value,
            'etat' => 1,
        ]);
    }

    /**
     * Ouvre automatiquement le mois suivant la dernière période connue.
     */
    public function ouvrirProchaine(int $entrepriseId): PeriodePaie
    {
        $derniere = PeriodePaie::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->orderByDesc('annee')->orderByDesc('mois')
            ->first();

        if (! $derniere) {
            return $this->ouvrir($entrepriseId, now()->year, now()->month);
        }

        $mois = $derniere->mois + 1;
        $annee = $derniere->annee;

        if ($mois > 12) {
            $mois = 1;
            $annee++;
        }

        return $this->ouvrir($entrepriseId, $annee, $mois);
    }
}