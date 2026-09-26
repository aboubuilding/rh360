<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Contrats\Enums\NatureEvenementEssai;
use App\Domain\Contrats\Enums\StatutEvenementEssai;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenementEssai extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'evenements_essai';

    protected $fillable = [
        'entreprise_id', 'contrat_id', 'cle_soumission',
        'nature', 'statut', 'details',
        'cree_par', 'decide_par', 'note_decision', 'decide_le',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'decide_le' => 'datetime',
            'nature' => NatureEvenementEssai::class,
            'statut' => StatutEvenementEssai::class,
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'decide_par');
    }

    public function estEnAttente(): bool
    {
        return $this->statut === StatutEvenementEssai::EN_ATTENTE;
    }

    // --- Helpers sur les détails JSON ---

    public function getDateDebutAttribute(): ?string
    {
        return $this->details['date_debut'] ?? null;
    }

    public function getDateFinAttribute(): ?string
    {
        return $this->details['date_fin'] ?? null;
    }

    public function getDureeJoursAttribute(): ?int
    {
        return $this->details['duree_jours'] ?? null;
    }
}