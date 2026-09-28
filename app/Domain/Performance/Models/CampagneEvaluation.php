<?php

namespace App\Domain\Performance\Models;

use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampagneEvaluation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'campagnes_evaluation';

    protected $fillable = [
        'entreprise_id', 'intitule', 'annee', 'date_debut', 'date_fin', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'statut' => StatutCampagne::class,
        ];
    }

    public function criteres(): HasMany
    {
        return $this->hasMany(CritereEvaluation::class, 'campagne_id');
    }

    public function objectifs(): HasMany
    {
        return $this->hasMany(ObjectifEvaluation::class, 'campagne_id');
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(EntretienEvaluation::class, 'campagne_id');
    }

    public function scopeAnnee(Builder $q, ?int $annee): Builder
    {
        return $q->when($annee, fn ($q) => $q->where('annee', $annee));
    }

    public function tauxRealisation(): float
    {
        $total = $this->entretiens()->count();
        if ($total === 0) return 0;

        $valides = $this->entretiens()->where('statut', 'valide')->count();
        return round(($valides / $total) * 100, 2);
    }
}