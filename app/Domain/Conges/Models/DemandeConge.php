<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeConge extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'demandes_conges';

    protected $fillable = [
        'entreprise_id', 'numero_demande', 'salarie_id', 'type_conge_id',
        'date_demande', 'date_debut', 'date_reprise', 'duree_jours',
        'motif', 'remplacant', 'statut', 'date_decision',
        'reference_acte', 'date_acte',
        'cree_par', 'valide_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_demande' => 'date',
            'date_debut' => 'date',
            'date_reprise' => 'date',
            'date_decision' => 'date',
            'date_acte' => 'date',
            'duree_jours' => 'decimal:2',
            'statut' => StatutDemandeConge::class,
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'valide_par');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('numero_demande', 'like', "%{$terme}%")
              ->orWhereHas('salarie', function ($q) use ($terme) {
                  $q->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenoms', 'like', "%{$terme}%")
                    ->orWhere('matricule', 'like', "%{$terme}%");
              });
        }));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeDeType(Builder $q, ?int $typeId): Builder
    {
        return $q->when($typeId, fn ($q) => $q->where('type_conge_id', $typeId));
    }

    public function scopeEnCours(Builder $q): Builder
    {
        return $q->where('statut', StatutDemandeConge::EN_COURS->value);
    }

    public function scopeProgrammees(Builder $q): Builder
    {
        return $q->whereIn('statut', [
            StatutDemandeConge::AUTORISEE->value,
            StatutDemandeConge::PROGRAMMEE->value,
        ])->where('date_debut', '>', now());
    }

    public function scopeReprisesEnRetard(Builder $q): Builder
    {
        return $q->where('statut', StatutDemandeConge::EN_COURS->value)
                 ->where('date_reprise', '<', now());
    }

    public function scopePeriode(Builder $q, ?string $du, ?string $au): Builder
    {
        return $q
            ->when($du, fn ($q) => $q->where('date_debut', '>=', $du))
            ->when($au, fn ($q) => $q->where('date_debut', '<=', $au));
    }

    // --- Helpers ---

    public function estEnRetard(): bool
    {
        return $this->statut === StatutDemandeConge::EN_COURS
            && $this->date_reprise
            && $this->date_reprise->isPast();
    }

    public function joursDepuisDebut(): ?int
    {
        return $this->date_debut?->diffInDays(now());
    }
}