<?php

namespace App\Domain\Classification\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionClassification extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'positions_classification';

    protected $fillable = [
        'referentiel_id', 'code', 'categorie_id', 'classe_id', 'echelon_id',
        'montant_salaire', 'salaire_minimum',
        'position_conformite_id', 'ordre', 'debut_effet', 'fin_effet',
        'position_suivante_id', 'actif', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'debut_effet' => 'date',
            'fin_effet' => 'date',
            'actif' => 'boolean',
            'montant_salaire' => 'integer',
            'salaire_minimum' => 'integer',
            'ordre' => 'integer',
        ];
    }

    public function referentiel(): BelongsTo
    {
        return $this->belongsTo(ReferentielClassification::class, 'referentiel_id');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ClasseClassification::class, 'classe_id');
    }

    public function echelon(): BelongsTo
    {
        return $this->belongsTo(EchelonClassification::class, 'echelon_id');
    }

    public function positionSuivante(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_suivante_id');
    }

    public function positionConformite(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_conformite_id');
    }

    public function libelleComplet(): string
    {
        return collect([
            $this->categorie?->libelle,
            $this->classe?->libelle,
            $this->echelon?->libelle,
        ])->filter()->implode(' - ');
    }
}