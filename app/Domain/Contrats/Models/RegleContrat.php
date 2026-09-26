<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegleContrat extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'regles_contrats';

    protected $fillable = [
        'entreprise_id', 'type_contrat', 'categorie_id', 'date_effet',
        'parametres', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_effet' => 'date',
            'parametres' => 'array',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Accessors utiles ---

    public function getDureeMaxEssaiAttribute(): ?int
    {
        return $this->parametres['duree_max_essai_jours'] ?? null;
    }

    public function getDureeMaxEssaiRenouvellementAttribute(): ?int
    {
        return $this->parametres['duree_max_essai_renouvellement_jours'] ?? null;
    }

    public function getNombreRenouvellementsMaxAttribute(): int
    {
        return (int) ($this->parametres['nombre_renouvellements_max'] ?? 0);
    }

    public function getPlafondRemunerationAttribute(): ?int
    {
        return $this->parametres['plafond_remuneration'] ?? null;
    }
}