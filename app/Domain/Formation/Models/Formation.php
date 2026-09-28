<?php

namespace App\Domain\Formation\Models;

use App\Domain\Formation\Enums\ModaliteFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'formations';

    protected $fillable = [
        'entreprise_id', 'code', 'intitule', 'domaine',
        'objectif', 'duree_heures', 'modalite', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'duree_heures' => 'decimal:2',
            'actif' => 'boolean',
            'modalite' => ModaliteFormation::class,
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SessionFormation::class, 'formation_id');
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('code', 'like', "%{$terme}%")
              ->orWhere('intitule', 'like', "%{$terme}%")
              ->orWhere('domaine', 'like', "%{$terme}%");
        }));
    }
}