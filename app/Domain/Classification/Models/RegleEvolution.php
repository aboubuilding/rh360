<?php

namespace App\Domain\Classification\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegleEvolution extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'regles_evolution';

    protected $fillable = [
        'referentiel_id', 'code', 'type_evolution', 'niveau_source',
        'intitule_source', 'reference_source', 'portee', 'priorite',
        'mois_min', 'mois_max', 'anticipation_autorisee', 'condition_anticipation',
        'mode_reinitialisation_anticipation', 'validation_requise',
        'regle_transitoire', 'debut_effet', 'fin_effet', 'actif', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'anticipation_autorisee' => 'boolean',
            'validation_requise' => 'boolean',
            'actif' => 'boolean',
            'debut_effet' => 'date',
            'fin_effet' => 'date',
            'priorite' => 'integer',
            'mois_min' => 'integer',
            'mois_max' => 'integer',
        ];
    }

    public function referentiel(): BelongsTo
    {
        return $this->belongsTo(ReferentielClassification::class, 'referentiel_id');
    }
}