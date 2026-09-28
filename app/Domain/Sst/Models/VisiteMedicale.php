<?php

namespace App\Domain\Sst\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisiteMedicale extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'visites_medicales';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'type_visite',
        'date_prevue', 'date_realisation', 'statut', 'aptitude',
        'prestataire', 'reference_avis', 'restrictions',
        'date_prochaine_echeance', 'motif_annulation',
        'visite_origine_id', 'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_prevue' => 'date',
            'date_realisation' => 'date',
            'date_prochaine_echeance' => 'date',
            'revision' => 'integer',
            'type_visite' => TypeVisiteMedicale::class,
            'statut' => StatutVisiteMedicale::class,
            'aptitude' => AptitudeMedicale::class,
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function visiteOrigine(): BelongsTo
    {
        return $this->belongsTo(VisiteMedicale::class, 'visite_origine_id');
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
        return $q->when($terme, fn ($q) => $q->whereHas('salarie', function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('prenoms', 'like', "%{$terme}%")
              ->orWhere('matricule', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeDeType(Builder $q, ?string $type): Builder
    {
        return $q->when($type, fn ($q) => $q->where('type_visite', $type));
    }

    public function scopeEcheancesProches(Builder $q, int $jours = 30): Builder
    {
        return $q->where('statut', StatutVisiteMedicale::PLANIFIEE->value)
                 ->whereBetween('date_prevue', [now(), now()->addDays($jours)]);
    }

    public function scopeEchues(Builder $q): Builder
    {
        return $q->where('statut', StatutVisiteMedicale::PLANIFIEE->value)
                 ->where('date_prevue', '<', now());
    }

    // --- Helpers ---

    public function estEchue(): bool
    {
        return $this->statut === StatutVisiteMedicale::PLANIFIEE
            && $this->date_prevue
            && $this->date_prevue->isPast();
    }

    public function aDesRestrictions(): bool
    {
        return ! empty($this->restrictions);
    }

    public function estApte(): bool
    {
        return in_array($this->aptitude, [AptitudeMedicale::APTE, AptitudeMedicale::APTE_AVEC_RESTRICTIONS], true);
    }
}