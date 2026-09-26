<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrat extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'contrats';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'parent_id',
        'cle_soumission', 'statut', 'reference', 'type_contrat',
        'date_debut', 'date_fin', 'poste_id', 'position_classification_id',
        'conditions', 'regle_figee', 'date_signature', 'reference_signee',
        'revision', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_signature' => 'date',
            'conditions' => 'array',
            'regle_figee' => 'array',
            'statut' => StatutContrat::class,
            'revision' => 'integer',
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'parent_id');
    }

    public function avenants(): HasMany
    {
        return $this->hasMany(Contrat::class, 'parent_id');
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_id');
    }

    public function positionClassification(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_classification_id');
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(PieceContrat::class, 'contrat_id');
    }

    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueContrat::class, 'contrat_id');
    }

    public function evenementsEssai(): HasMany
    {
        return $this->hasMany(EvenementEssai::class, 'contrat_id');
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(AlerteContrat::class, 'contrat_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('reference', 'like', "%{$terme}%")
              ->orWhereHas('salarie', function ($q) use ($terme) {
                  $q->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenoms', 'like', "%{$terme}%")
                    ->orWhere('matricule', 'like', "%{$terme}%");
              });
        }));
    }

    public function scopeDeType(Builder $q, ?string $type): Builder
    {
        return $q->when($type, fn ($q) => $q->where('type_contrat', $type));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopePeriodeEffet(Builder $q, ?string $du, ?string $au): Builder
    {
        return $q
            ->when($du, fn ($q) => $q->where('date_debut', '>=', $du))
            ->when($au, fn ($q) => $q->where('date_debut', '<=', $au));
    }

    // --- Helpers ---

    public function estAvenant(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function estOrigine(): bool
    {
        return is_null($this->parent_id);
    }

    public function estModifiable(): bool
    {
        return in_array($this->statut, [StatutContrat::BROUILLON, StatutContrat::SOUMIS], true);
    }

    public function estSigne(): bool
    {
        return $this->statut === StatutContrat::SIGNE;
    }

    public function estEnEssai(): bool
    {
        return $this->evenementsEssai()
            ->whereIn('nature', ['suspension', 'renouvellement'])
            ->where('statut', 'approved')
            ->exists();
    }

    public function libelleComplet(): string
    {
        $type = $this->estAvenant() ? 'Avenant' : 'Contrat';
        return "{$type} {$this->reference}";
    }
}