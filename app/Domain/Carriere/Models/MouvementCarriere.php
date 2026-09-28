<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MouvementCarriere extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'mouvements_carriere';

    protected $fillable = [
        'entreprise_id', 'numero_mouvement', 'salarie_id',
        'type_mouvement', 'sous_type_mouvement',
        'type_source', 'reference_source', 'motif',
        'statut',
        'date_proposition', 'date_eligibilite', 'date_decision',
        'date_acte', 'date_effet', 'date_notification',
        'date_controle', 'date_fin_prevue', 'date_fin_reelle',
        'motif_cloture',
        'structure_depart_id', 'structure_cible_id',
        'poste_depart_id', 'poste_cible_id',
        'position_classification_depart_id', 'position_classification_cible_id',
        'lieu_affectation_depart', 'lieu_affectation_cible',
        'reference_acte', 'chemin_justificatif',
        'type_saisie_source', 'reference_lot_reprise',
        'cree_par', 'controle_par', 'valide_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_proposition'  => 'date',
            'date_eligibilite'  => 'date',
            'date_decision'     => 'date',
            'date_acte'         => 'date',
            'date_effet'        => 'date',
            'date_notification' => 'date',
            'date_controle'     => 'date',
            'date_fin_prevue'   => 'date',
            'date_fin_reelle'   => 'date',
            'statut'            => StatutMouvement::class,
            'type_mouvement'    => TypeMouvement::class,
            'type_source'       => TypeSourceSituation::class,
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function structureDepart(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_depart_id');
    }

    public function structureCible(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_cible_id');
    }

    public function posteDepart(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_depart_id');
    }

    public function posteCible(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_cible_id');
    }

    public function positionDepart(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_classification_depart_id');
    }

    public function positionCible(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_classification_cible_id');
    }

    public function instantane(): HasOne
    {
        return $this->hasOne(InstantaneCarriere::class, 'mouvement_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function controlePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'controle_par');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'valide_par');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('numero_mouvement', 'like', "%{$terme}%")
              ->orWhereHas('salarie', function ($q) use ($terme) {
                  $q->where('nom', 'like', "%{$terme}%")
                    ->orWhere('prenoms', 'like', "%{$terme}%")
                    ->orWhere('matricule', 'like', "%{$terme}%");
              });
        }));
    }

    public function scopeDeType(Builder $q, ?string $type): Builder
    {
        return $q->when($type, fn ($q) => $q->where('type_mouvement', $type));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeEnCircuit(Builder $q): Builder
    {
        return $q->whereIn('statut', [
            StatutMouvement::PROPOSE->value,
            StatutMouvement::A_VERIFIER->value,
            StatutMouvement::VERIFIE->value,
            StatutMouvement::VALIDE->value,
            StatutMouvement::PROGRAMME->value,
        ]);
    }

    public function scopeProgrammes(Builder $q): Builder
    {
        return $q->where('statut', StatutMouvement::PROGRAMME->value)
                 ->where('date_effet', '<=', now());
    }

    public function scopeEcheanceProche(Builder $q, int $jours = 90): Builder
    {
        return $q->whereNotNull('date_eligibilite')
                 ->whereBetween('date_eligibilite', [now(), now()->addDays($jours)]);
    }

    public function scopePeriode(Builder $q, ?string $du, ?string $au, string $base = 'date_effet'): Builder
    {
        return $q
            ->when($du, fn ($q) => $q->where($base, '>=', $du))
            ->when($au, fn ($q) => $q->where($base, '<=', $au));
    }

    // --- Helpers ---

    public function estAffectation(): bool
    {
        return $this->type_mouvement->estAffectation();
    }

    public function estCarriere(): bool
    {
        return $this->type_mouvement->estCarriere();
    }

    public function estTemporaire(): bool
    {
        return $this->type_mouvement->estTemporaire();
    }

    public function peutEtreApplique(): bool
    {
        return $this->statut === StatutMouvement::PROGRAMME
            && $this->date_effet
            && $this->date_effet->isPast();
    }

    public function prochaineEtape(): ?string
    {
        return match ($this->statut) {
            StatutMouvement::BROUILLON  => 'Soumettre la proposition',
            StatutMouvement::PROPOSE    => 'Contrôle RH',
            StatutMouvement::A_VERIFIER => 'Vérification DRH',
            StatutMouvement::VERIFIE    => 'Validation DRH',
            StatutMouvement::VALIDE     => 'Programmation à la date d\'effet',
            StatutMouvement::PROGRAMME  => 'Application automatique à la date d\'effet',
            StatutMouvement::EFFECTIF   => 'Finalisation',
            default                     => null,
        };
    }

    public function rappels(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Domain\Paie\Models\RappelAvancement::class, 'mouvement_id');
}
}