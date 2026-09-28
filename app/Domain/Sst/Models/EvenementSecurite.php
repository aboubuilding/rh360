<?php

namespace App\Domain\Sst\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\StatutExterneEvenement;
use App\Domain\Sst\Enums\TypeEvenementSecurite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvenementSecurite extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'evenements_securite';

    protected $fillable = [
        'entreprise_id', 'cle_soumission', 'type_evenement',
        'date_survenance', 'heure_survenance', 'date_declaration',
        'intitule', 'localisation', 'description', 'mesures_immediates',
        'analyse', 'priorite', 'statut', 'statut_externe',
        'destinataire_externe', 'date_echeance_externe',
        'date_envoi_externe', 'reference_externe',
        'date_cloture', 'synthese_cloture', 'motif_annulation',
        'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_survenance' => 'date',
            'date_declaration' => 'date',
            'date_echeance_externe' => 'date',
            'date_envoi_externe' => 'date',
            'date_cloture' => 'date',
            'revision' => 'integer',
            'type_evenement' => TypeEvenementSecurite::class,
            'statut' => StatutEvenementSecurite::class,
            'statut_externe' => StatutExterneEvenement::class,
        ];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Personnel\Models\Salarie::class,
            'participants_evenements',
            'evenement_id',
            'salarie_id'
        )->withTimestamps();
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ActionSecurite::class, 'evenement_id');
    }

    public function piecesJointes(): HasMany
    {
        return $this->hasMany(PieceJointeSst::class, 'fiche_id')
            ->where('nature', 'evenement_securite');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function modifiePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'modifie_par');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('intitule', 'like', "%{$terme}%")
              ->orWhere('localisation', 'like', "%{$terme}%")
              ->orWhere('description', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeType(Builder $q, ?string $type): Builder
    {
        return $q->when($type, fn ($q) => $q->where('type_evenement', $type));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeEnCours(Builder $q): Builder
    {
        return $q->whereIn('statut', [
            StatutEvenementSecurite::DECLARE->value,
            StatutEvenementSecurite::EN_TRAITEMENT->value,
        ]);
    }

    public function scopePeriode(Builder $q, ?string $du, ?string $au): Builder
    {
        return $q
            ->when($du, fn ($q) => $q->where('date_survenance', '>=', $du))
            ->when($au, fn ($q) => $q->where('date_survenance', '<=', $au));
    }

    // --- Helpers ---

    public function estCloturable(): bool
    {
        return in_array($this->statut, [
            StatutEvenementSecurite::DECLARE,
            StatutEvenementSecurite::EN_TRAITEMENT,
        ], true);
    }

    public function aDesActionsEnRetard(): bool
    {
        return $this->actions()
            ->where('statut', '!=', 'done')
            ->where('date_echeance', '<', now())
            ->exists();
    }
}