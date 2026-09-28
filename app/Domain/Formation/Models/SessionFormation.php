<?php

namespace App\Domain\Formation\Models;

use App\Domain\Formation\Enums\StatutSessionFormation;
use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionFormation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'sessions_formation';

    protected $fillable = [
        'entreprise_id', 'plan_formation_id', 'intitule',
        'prestataire', 'localisation', 'date_debut', 'date_fin',
        'duree_heures', 'cout_reel', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'duree_heures' => 'decimal:2',
            'cout_reel' => 'decimal:2',
            'statut' => StatutSessionFormation::class,
        ];
    }

    public function planFormation(): BelongsTo
    {
        return $this->belongsTo(PlanFormation::class, 'plan_formation_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ParticipantFormation::class, 'session_formation_id');
    }

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('intitule', 'like', "%{$terme}%")
              ->orWhere('prestataire', 'like', "%{$terme}%")
              ->orWhere('localisation', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function tauxPresence(): float
    {
        $total = $this->participants()->count();
        if ($total === 0) return 0;

        $presents = $this->participants()->where('statut_presence', 'present')->count();
        return round(($presents / $total) * 100, 2);
    }
}