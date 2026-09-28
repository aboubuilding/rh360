<?php

namespace App\Domain\Formation\Models;

use App\Domain\Formation\Enums\StatutPresenceParticipant;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantFormation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'participants_formation';

    protected $fillable = [
        'entreprise_id', 'session_formation_id', 'salarie_id',
        'statut_presence', 'score_avant', 'score_apres',
        'note_satisfaction', 'commentaire_evaluation',
        'reference_attestation', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'score_avant' => 'decimal:2',
            'score_apres' => 'decimal:2',
            'note_satisfaction' => 'decimal:2',
            'statut_presence' => StatutPresenceParticipant::class,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionFormation::class, 'session_formation_id');
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function progression(): ?float
    {
        if ($this->score_avant === null || $this->score_apres === null) {
            return null;
        }
        return round((float) $this->score_apres - (float) $this->score_avant, 2);
    }
}