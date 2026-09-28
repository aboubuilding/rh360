<?php

namespace App\Domain\Sst\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\StatutHabilitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habilitation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'habilitations';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'risque_id', 'habilitation_origine_id',
        'cle_soumission', 'categorie', 'intitule', 'portee',
        'emetteur', 'reference_decision', 'reference_formation',
        'date_decision', 'date_debut', 'date_fin', 'date_revue',
        'statut', 'motif', 'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_decision' => 'date',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_revue' => 'date',
            'revision' => 'integer',
            'statut' => StatutHabilitation::class,
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function risque(): BelongsTo
    {
        return $this->belongsTo(Risque::class, 'risque_id');
    }

    public function habilitationOrigine(): BelongsTo
    {
        return $this->belongsTo(Habilitation::class, 'habilitation_origine_id');
    }

    public function renouvellements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Habilitation::class, 'habilitation_origine_id');
    }

    // --- Scopes ---

    public function scopeActives(Builder $q): Builder
    {
        return $q->where('statut', StatutHabilitation::ACTIVE->value);
    }

    public function scopeEcheanceProche(Builder $q, int $jours = 60): Builder
    {
        return $q->whereNotNull('date_fin')
                 ->whereBetween('date_fin', [now(), now()->addDays($jours)]);
    }

    public function scopeExpirees(Builder $q): Builder
    {
        return $q->where('date_fin', '<', now())
                 ->where('statut', StatutHabilitation::ACTIVE->value);
    }

    // --- Helpers ---

    public function estExpiree(): bool
    {
        return $this->date_fin && $this->date_fin->isPast();
    }

    public function joursRestants(): ?int
    {
        return $this->date_fin?->diffInDays(now(), false);
    }
}