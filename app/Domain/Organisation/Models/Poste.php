<?php

namespace App\Domain\Organisation\Models;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poste extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'postes';

    protected $fillable = [
        'entreprise_id', 'structure_id', 'code', 'intitule',
        'categorie', 'categorie_classification_id', 'effectif_cible',
        'travaux_dangereux', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'effectif_cible' => 'integer',
            'travaux_dangereux' => 'boolean',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }

    // RG-INT-01 — catégorie de classification du poste, comparée à celle du salarié.
    public function categorieClassification(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_classification_id');
    }
}