<?php

namespace App\Domain\Sst\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\NiveauRisque;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationRisque extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'evaluations_risques';

    protected $fillable = [
        'entreprise_id', 'risque_id', 'cle_soumission',
        'date_evaluation', 'motif', 'gravite', 'probabilite',
        'score', 'niveau', 'version_methode',
        'mesures_existantes', 'justification', 'date_prochaine_revue',
        'revision_perimetre', 'revision_mesures',
        'instantane_contexte', 'cree_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_evaluation' => 'date',
            'date_prochaine_revue' => 'date',
            'gravite' => 'integer',
            'probabilite' => 'integer',
            'score' => 'integer',
            'revision_perimetre' => 'integer',
            'revision_mesures' => 'integer',
            'instantane_contexte' => 'array',
            'niveau' => NiveauRisque::class,
        ];
    }

    public function risque(): BelongsTo
    {
        return $this->belongsTo(Risque::class, 'risque_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Accessors ---

    public function getScoreCalculeAttribute(): int
    {
        return ($this->gravite ?? 1) * ($this->probabilite ?? 1);
    }
}