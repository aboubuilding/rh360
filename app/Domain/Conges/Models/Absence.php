<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'absences';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'type_conge_id',
        'debut_le', 'fin_le', 'duree_heures',
        'motif', 'justification', 'date_limite_justification',
        'statut', 'decision_regularisation', 'qualification',
        'statut_transmission_paie', 'periode_paie',
        'transmis_paie_le', 'transmis_paie_par', 'cree_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'debut_le' => 'datetime',
            'fin_le' => 'datetime',
            'date_limite_justification' => 'date',
            'transmis_paie_le' => 'datetime',
            'duree_heures' => 'decimal:2',
            'qualification' => QualificationAbsence::class,
            'statut_transmission_paie' => StatutTransmissionPaie::class,
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

    public function transmisPaiePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'transmis_paie_par');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
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

    public function scopeOuvertes(Builder $q): Builder
    {
        return $q->whereNull('fin_le');
    }

    public function scopeEnAttenteQualification(Builder $q): Builder
    {
        return $q->where('qualification', QualificationAbsence::EN_ATTENTE->value);
    }

    public function scopeATransmettre(Builder $q): Builder
    {
        return $q->whereIn('statut_transmission_paie', [
            StatutTransmissionPaie::A_PREPARER->value,
            StatutTransmissionPaie::PREPARE->value,
        ]);
    }

    // --- Helpers ---

    public function estOuverte(): bool
    {
        return is_null($this->fin_le);
    }

    public function estTransmise(): bool
    {
        return in_array($this->statut_transmission_paie, [
            StatutTransmissionPaie::TRANSMIS,
            StatutTransmissionPaie::INTEGRE,
        ], true);
    }

    public function dureeJours(): float
    {
        if (! $this->debut_le) return 0;
        $fin = $this->fin_le ?? now();
        return $this->debut_le->diffInDays($fin) + 1;
    }
}