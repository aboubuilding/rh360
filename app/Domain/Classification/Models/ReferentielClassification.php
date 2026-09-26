<?php

namespace App\Domain\Classification\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferentielClassification extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'referentiels_classification';

    protected $fillable = [
        'entreprise_id', 'code', 'nom', 'type_referentiel', 'niveau_source',
        'intitule_source', 'reference_source', 'portee', 'priorite',
        'debut_effet', 'fin_effet', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'debut_effet' => 'date',
            'fin_effet' => 'date',
            'actif' => 'boolean',
            'priorite' => 'integer',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CategorieClassification::class, 'referentiel_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClasseClassification::class, 'referentiel_id');
    }

    public function echelons(): HasMany
    {
        return $this->hasMany(EchelonClassification::class, 'referentiel_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PositionClassification::class, 'referentiel_id');
    }

    public function reglesEvolution(): HasMany
    {
        return $this->hasMany(RegleEvolution::class, 'referentiel_id');
    }
}