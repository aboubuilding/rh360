<?php

namespace App\Domain\Classification\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClasseClassification extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'classes_classification';

    protected $fillable = ['referentiel_id', 'code', 'libelle', 'ordre', 'actif', 'etat'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'ordre' => 'integer'];
    }

    public function referentiel(): BelongsTo
    {
        return $this->belongsTo(ReferentielClassification::class, 'referentiel_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PositionClassification::class, 'classe_id');
    }
}