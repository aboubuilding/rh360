<?php

namespace App\Domain\Interim\Models;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\DecisionFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Database\Factories\InterimInterneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Intérim interne d'un emploi de catégorie supérieure (CCIT art. 13).
 */
class InterimInterne extends Model
{
    use AvecEtat, BelongsToEntreprise, HasFactory;

    protected $table = 'interims_internes';

    protected $fillable = [
        'entreprise_id', 'mouvement_carriere_id',
        'salarie_id', 'poste_occupe_id', 'categorie_occupee_id', 'categorie_origine_id',
        'titulaire_id', 'absence_titulaire_id', 'motif', 'precision_motif',
        'date_debut', 'date_fin_prevue', 'date_fin_motif', 'date_limite', 'date_fin_reelle',
        'salaire_base_origine', 'position_origine_id', 'affectation_origine_id',
        'statut', 'cause_fin', 'decision_fin', 'date_decision', 'decide_par', 'motif_cloture',
        'reference_lot_reprise', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'motif' => MotifInterim::class,
            'statut' => StatutInterim::class,
            'cause_fin' => CauseFinInterim::class,
            'decision_fin' => DecisionFinInterim::class,
            'date_debut' => 'date',
            'date_fin_prevue' => 'date',
            'date_fin_motif' => 'date',
            'date_limite' => 'date',
            'date_fin_reelle' => 'date',
            'date_decision' => 'date',
            'salaire_base_origine' => 'integer',
        ];
    }

    protected static function newFactory(): InterimInterneFactory
    {
        return InterimInterneFactory::new();
    }

    // --- Relations ---

    public function mouvementCarriere(): BelongsTo
    {
        return $this->belongsTo(MouvementCarriere::class, 'mouvement_carriere_id');
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function posteOccupe(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_occupe_id');
    }

    public function categorieOccupee(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_occupee_id');
    }

    public function categorieOrigine(): BelongsTo
    {
        return $this->belongsTo(CategorieClassification::class, 'categorie_origine_id');
    }

    public function titulaire(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'titulaire_id');
    }

    public function absenceTitulaire(): BelongsTo
    {
        return $this->belongsTo(Absence::class, 'absence_titulaire_id');
    }

    public function positionOrigine(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_origine_id');
    }

    public function affectationOrigine(): BelongsTo
    {
        return $this->belongsTo(Affectation::class, 'affectation_origine_id');
    }

    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'decide_par');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'cree_par');
    }

    public function decisions(): MorphMany
    {
        return $this->morphMany(DecisionInterim::class, 'objet')->orderBy('decide_le');
    }

    public function avantages(): HasMany
    {
        return $this->hasMany(AvantageInterim::class, 'interim_interne_id');
    }

    // --- Scopes ---

    public function scopeEnVigueur(Builder $q): Builder
    {
        return $q->whereIn('statut', StatutInterim::valeursActives());
    }

    public function scopeARegulariser(Builder $q): Builder
    {
        return $q->where('statut', StatutInterim::A_REGULARISER->value);
    }

    // --- Règles d'état ---

    public function estClos(): bool
    {
        return $this->statut === StatutInterim::CLOS;
    }

    // RG-INT-04 — prolongation au-delà de deux mois réservée aux motifs éligibles.
    public function estProlongeable(): bool
    {
        return ! $this->estClos() && $this->motif->estEligibleProlongation();
    }
}
