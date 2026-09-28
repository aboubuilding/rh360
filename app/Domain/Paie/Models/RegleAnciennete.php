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

    protected $fillable = [
        'entreprise_id', 'annees_min', 'taux_initial',
        'increment_annuel', 'taux_max', 'mode_base',
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
     */
    public function calculerTaux(int $anneesService): float
    {
        if ($anneesService < $this->annees_min) {
            return 0.0;
        }

        $anneesEligibles = $anneesService - $this->annees_min;
        $taux = (float) $this->taux_initial + ($anneesEligibles * (float) $this->increment_annuel);

        return min($taux, (float) $this->taux_max);
    }
}