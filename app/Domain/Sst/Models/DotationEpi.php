<?php

namespace App\Domain\Sst\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\CategorieEpi;
use App\Domain\Sst\Enums\StatutDotationEpi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DotationEpi extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'dotations_epi';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'risque_id', 'cle_soumission',
        'categorie', 'intitule', 'quantite', 'unite',
        'numero_serie', 'taille', 'date_remise',
        'date_expiration', 'date_verification',
        'emetteur', 'reference_recu', 'statut', 'motif',
        'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_remise' => 'date',
            'date_expiration' => 'date',
            'date_verification' => 'date',
            'quantite' => 'integer',
            'revision' => 'integer',
            'categorie' => CategorieEpi::class,
            'statut' => StatutDotationEpi::class,
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

    public function operations(): HasMany
    {
        return $this->hasMany(OperationEpi::class, 'dotation_id')->orderByDesc('date_evenement');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Scopes ---

    public function scopeActives(Builder $q): Builder
    {
        return $q->whereIn('statut', [StatutDotationEpi::REMIS->value, StatutDotationEpi::EN_USAGE->value]);
    }

    public function scopeExpirant(Builder $q, int $jours = 30): Builder
    {
        return $q->whereNotNull('date_expiration')
                 ->whereBetween('date_expiration', [now(), now()->addDays($jours)]);
    }

    public function scopeAVerifier(Builder $q, int $jours = 30): Builder
    {
        return $q->whereNotNull('date_verification')
                 ->whereBetween('date_verification', [now(), now()->addDays($jours)]);
    }

    // --- Helpers ---

    public function quantiteRestante(): int
    {
        $operations = $this->operations()
            ->whereIn('nature', ['restitution', 'perte', 'mise_au_rebut'])
            ->sum('quantite');

        return max(0, $this->quantite - $operations);
    }

    public function estExpire(): bool
    {
        return $this->date_expiration && $this->date_expiration->isPast();
    }
}