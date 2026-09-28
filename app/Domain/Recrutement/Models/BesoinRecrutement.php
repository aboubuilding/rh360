<?php

namespace App\Domain\Recrutement\Models;

use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BesoinRecrutement extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'besoins_recrutement';

    protected $fillable = [
        'entreprise_id', 'reference', 'intitule_poste', 'departement',
        'nombre_postes', 'type_contrat', 'date_cible', 'motif',
        'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'nombre_postes' => 'integer',
            'date_cible' => 'date',
            'statut' => StatutBesoinRecrutement::class,
        ];
    }

    public function candidats(): HasMany
    {
        return $this->hasMany(Candidat::class, 'besoin_id');
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('reference', 'like', "%{$terme}%")
              ->orWhere('intitule_poste', 'like', "%{$terme}%")
              ->orWhere('departement', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function nombreCandidatsRecrutes(): int
    {
        return $this->candidats()->where('decision', 'retenu')->count();
    }

    public function estPourvu(): bool
    {
        return $this->nombreCandidatsRecrutes() >= $this->nombre_postes;
    }
}