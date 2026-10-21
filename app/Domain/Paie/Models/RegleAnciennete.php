<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegleAnciennete extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'regles_anciennete';

    // Base de la prime : salaire minimum de base de la catégorie (échelon de référence), de l'échelon
    // du salarié, ou salaire de base réel (plus favorable).
    public const MODES_BASE = [
        'echelon_a' => 'Minimum de la catégorie (échelon A)',
        'echelon_salarie' => 'Minimum de l\'échelon du salarié',
        'salary_base' => 'Salaire de base réel',
    ];

    public const MODES_TAUX = [
        'ccit_cumul' => 'Taux initial, puis + incrément par année à partir de l\'année de début (cumul)',
        'ccit_multiplicatif' => 'Taux initial, puis incrément × années de présence à partir de l\'année de début',
        'lineaire' => 'Taux initial, puis + incrément par année au-delà du minimum',
    ];

    protected $fillable = [
        'entreprise_id', 'annees_min', 'taux_initial',
        'increment_annuel', 'taux_max', 'mode_taux', 'annee_debut_increment', 'mode_base',
        'debut_effet', 'fin_effet', 'reference_legale',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annees_min' => 'integer',
            'taux_initial' => 'decimal:4',
            'increment_annuel' => 'decimal:2',
            'taux_max' => 'decimal:4',
            'annee_debut_increment' => 'integer',
            'debut_effet' => 'date',
            'fin_effet' => 'date',
            'actif' => 'boolean',
        ];
    }

    public function scopeEnVigueur(Builder $q, $date): Builder
    {
        return $q->where('debut_effet', '<=', $date)
                 ->where(function ($q) use ($date) {
                     $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date);
                 })
                 ->where('actif', true);
    }

    /**
     * Calcule le taux d'ancienneté pour un nombre d'années de service.
     * - « lineaire » : taux initial à annees_min, puis + incrément par année au-delà ;
     * - « ccit_cumul » (CCIT art. 40, lecture cumulative) : taux initial à annees_min, puis + incrément
     *   par année à partir de annee_debut_increment (2 % à 2 ans, 3 % à 4 ans, 35 % à 36 ans) ;
     * - « ccit_multiplicatif » : taux initial à annees_min, puis incrément × années à partir de
     *   annee_debut_increment (2 % à 2 ans, 4 % à 4 ans, 35 % à 35 ans).
     */
    public function calculerTaux(int $anneesService): float
    {
        if ($anneesService < $this->annees_min) {
            return 0.0;
        }

        $initial = (float) $this->taux_initial;
        $increment = (float) $this->increment_annuel;
        $debutIncrement = $this->annee_debut_increment ?? ($this->annees_min + 1);

        $taux = match ($this->mode_taux) {
            'ccit_cumul' => $anneesService < $debutIncrement
                ? $initial
                : $initial + ($anneesService - $debutIncrement + 1) * $increment,
            'ccit_multiplicatif' => $anneesService < $debutIncrement
                ? $initial
                : $anneesService * $increment,
            default => $initial + ($anneesService - $this->annees_min) * $increment,
        };

        return min($taux, (float) $this->taux_max);
    }
}