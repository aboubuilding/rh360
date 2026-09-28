<?php

namespace App\Domain\Paie\Models;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelePaie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'modeles_paie';

    protected $fillable = [
        'entreprise_id', 'categorie_id', 'nom', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_id');
    }

    public function rubriques(): HasMany
    {
        return $this->hasMany(ModelePaieRubrique::class, 'modele_id');
    }

    public function rubriquesActives(): HasMany
    {
        return $this->rubriques()->where('actif', true)->orderBy('ordre');
    }
}