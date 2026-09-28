<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegleCotisation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'regles_cotisations';

    protected $fillable = [
        'entreprise_id', 'code', 'nom',
        'taux_salarial', 'taux_patronal',
        'debut_effet', 'fin_effet',
        'reference_legale', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'taux_salarial' => 'decimal:4',
            'taux_patronal' => 'decimal:4',
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
}