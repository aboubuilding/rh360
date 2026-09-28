<?php

namespace App\Domain\Paie\Models;

use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePaie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'periodes_paie';

    protected $fillable = [
        'entreprise_id', 'annee', 'mois', 'statut',
        'valide_le', 'valide_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'mois' => 'integer',
            'valide_le' => 'datetime',
            'statut' => StatutPeriode::class,
        ];
    }

    // --- Relations ---

    public function bulletins(): HasMany
    {
        return $this->hasMany(BulletinPaie::class, 'periode_id');
    }

    public function saisies(): HasMany
    {
        return $this->hasMany(SaisiePaie::class, 'periode_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'valide_par');
    }

    // --- Scopes ---

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeRecentes(Builder $q): Builder
    {
        return $q->orderByDesc('annee')->orderByDesc('mois');
    }

    // --- Accessors ---

    public function getLibelleAttribute(): string
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($mois[$this->mois] ?? '?') . ' ' . $this->annee;
    }

    public function getCodeAttribute(): string
    {
        return sprintf('%04d-%02d', $this->annee, $this->mois);
    }

    public function peutEtreCalculee(): bool
    {
        return $this->statut->peutCalculer();
    }

    public function estFigee(): bool
    {
        return $this->statut->estFigee();
    }
}