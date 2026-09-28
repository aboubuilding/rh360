<?php

namespace App\Domain\Recrutement\Models;

use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Enums\SourceCandidat;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidat extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'candidats';

    protected $fillable = [
        'entreprise_id', 'besoin_id', 'nom', 'prenoms',
        'email', 'telephone', 'source', 'etape',
        'score', 'date_entretien', 'decision',
        'date_integration', 'observations', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_entretien' => 'datetime',
            'date_integration' => 'date',
            'score' => 'decimal:2',
            'etape' => EtapeCandidat::class,
            'source' => SourceCandidat::class,
            'decision' => DecisionCandidat::class,
        ];
    }

    public function besoin(): BelongsTo
    {
        return $this->belongsTo(BesoinRecrutement::class, 'besoin_id');
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenoms);
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('prenoms', 'like', "%{$terme}%")
              ->orWhere('email', 'like', "%{$terme}%")
              ->orWhere('telephone', 'like', "%{$terme}%");
        }));
    }

    public function scopeParEtape(Builder $q, ?string $etape): Builder
    {
        return $q->when($etape, fn ($q) => $q->where('etape', $etape));
    }

    public function scopeParDecision(Builder $q, ?string $decision): Builder
    {
        return $q->when($decision, fn ($q) => $q->where('decision', $decision));
    }

    public function scopeEnCours(Builder $q): Builder
    {
        return $q->whereNotIn('etape', ['accepte', 'refuse', 'retire']);
    }

    public function estEnCours(): bool
    {
        return ! $this->etape->estFinal();
    }
}