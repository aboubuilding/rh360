<?php

namespace App\Domain\Performance\Models;

use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntretienEvaluation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'entretiens_evaluation';

    protected $fillable = [
        'entreprise_id', 'campagne_id', 'salarie_id',
        'note_auto_evaluation', 'note_manager', 'note_finale',
        'points_forts', 'besoins_developpement', 'commentaire_manager',
        'statut', 'date_entretien', 'action_amelioration',
        'date_echeance_amelioration', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'note_auto_evaluation' => 'decimal:2',
            'note_manager' => 'decimal:2',
            'note_finale' => 'decimal:2',
            'date_entretien' => 'datetime',
            'date_echeance_amelioration' => 'date',
            'statut' => StatutEntretien::class,
        ];
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneEvaluation::class, 'campagne_id');
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function scopeDeStatut(Builder $q, ?string $statut): Builder
    {
        return $q->when($statut, fn ($q) => $q->where('statut', $statut));
    }

    public function scopeCampagne(Builder $q, ?int $campagneId): Builder
    {
        return $q->when($campagneId, fn ($q) => $q->where('campagne_id', $campagneId));
    }
}