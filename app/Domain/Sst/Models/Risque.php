<?php

namespace App\Domain\Sst\Models;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\FamilleRisque;
use App\Domain\Sst\Enums\StatutActionSecurite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Risque extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'risques';

    protected $fillable = [
        'entreprise_id', 'cle_soumission', 'intitule', 'famille',
        'site', 'poste_id', 'activite', 'danger', 'consequences',
        'date_identification', 'responsable_salarie_id',
        'date_echeance_revue', 'statut', 'motif_archivage',
        'revision_perimetre', 'revision_mesures',
        'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_identification' => 'date',
            'date_echeance_revue' => 'date',
            'revision_perimetre' => 'integer',
            'revision_mesures' => 'integer',
            'revision' => 'integer',
            'famille' => FamilleRisque::class,
        ];
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'responsable_salarie_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EvaluationRisque::class, 'risque_id')
            ->orderByDesc('date_evaluation');
    }

    public function derniereEvaluation(): ?EvaluationRisque
    {
        return $this->evaluations()->first();
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ActionRisque::class, 'risque_id');
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
              ->orWhere('site', 'like', "%{$terme}%")
              ->orWhere('activite', 'like', "%{$terme}%")
              ->orWhere('danger', 'like', "%{$terme}%");
        }));
    }

    public function scopeActifs(Builder $q): Builder
    {
        return $q->where('statut', 'active');
    }

    public function scopeParFamille(Builder $q, ?string $famille): Builder
    {
        return $q->when($famille, fn ($q) => $q->where('famille', $famille));
    }

    public function scopeEcheanceRevueProche(Builder $q, int $jours = 30): Builder
    {
        return $q->whereNotNull('date_echeance_revue')
                 ->whereBetween('date_echeance_revue', [now(), now()->addDays($jours)]);
    }

    // --- Helpers ---

    public function scoreDerniereEvaluation(): ?int
    {
        return $this->derniereEvaluation()?->score;
    }

    public function niveauDerniereEvaluation(): ?\App\Domain\Sst\Enums\NiveauRisque
    {
        $score = $this->scoreDerniereEvaluation();
        return $score !== null ? \App\Domain\Sst\Enums\NiveauRisque::depuisScore($score) : null;
    }

    public function aDesActionsEnRetard(): bool
    {
        return $this->actions()
            ->where('statut', '!=', StatutActionSecurite::REALISEE->value)
            ->where('date_echeance', '<', now())
            ->exists();
    }
}