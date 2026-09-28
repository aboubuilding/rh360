<?php

namespace App\Domain\Formation\Models;

use App\Domain\Formation\Enums\PrioriteBesoinFormation;
use App\Domain\Formation\Enums\StatutBesoinFormation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BesoinFormation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'besoins_formation';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'intitule', 'motif',
        'priorite', 'annee_cible', 'statut', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annee_cible' => 'integer',
            'priorite' => PrioriteBesoinFormation::class,
            'statut' => StatutBesoinFormation::class,
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('intitule', 'like', "%{$terme}%")
              ->orWhere('motif', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeAnnee(Builder $q, ?int $annee): Builder
    {
        return $q->when($annee, fn ($q) => $q->where('annee_cible', $annee));
    }
}