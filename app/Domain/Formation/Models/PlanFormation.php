<?php

namespace App\Domain\Formation\Models;

use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanFormation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'plan_formation';

    protected $fillable = [
        'entreprise_id', 'intitule', 'annee', 'debut_prevu',
        'montant_budget', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'debut_prevu' => 'date',
            'montant_budget' => 'decimal:2',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SessionFormation::class, 'plan_formation_id');
    }

    public function scopeAnnee(Builder $q, ?int $annee): Builder
    {
        return $q->when($annee, fn ($q) => $q->where('annee', $annee));
    }

    public function budgetConsomme(): float
    {
        return (float) $this->sessions()->sum('cout_reel');
    }

    public function budgetRestant(): float
    {
        return (float) $this->montant_budget - $this->budgetConsomme();
    }
}