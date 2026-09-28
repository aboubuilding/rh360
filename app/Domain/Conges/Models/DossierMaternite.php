<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\StatutDossierMaternite;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierMaternite extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'dossiers_maternite';

    protected $fillable = [
        'entreprise_id', 'salarie_id',
        'date_declaration', 'chemin_certificat_medical',
        'risque_poste_identifie', 'amenagement_temporaire', 'statut',
        'date_consultation_1', 'date_consultation_2', 'date_consultation_3',
        'date_prevue_accouchement', 'date_debut_conge', 'date_reprise_effective',
        'reference_acte', 'date_acte', 'observations', 'cree_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_declaration' => 'date',
            'date_consultation_1' => 'date',
            'date_consultation_2' => 'date',
            'date_consultation_3' => 'date',
            'date_prevue_accouchement' => 'date',
            'date_debut_conge' => 'date',
            'date_reprise_effective' => 'date',
            'date_acte' => 'date',
            'risque_poste_identifie' => 'boolean',
            'statut' => StatutDossierMaternite::class,
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Scopes ---

    public function scopeConfidentielPour(Builder $q, int $utilisateurId): Builder
    {
        // Le CDC indique que ce registre n'est consultable que par les
        // utilisateurs avec la permission sensitive.social_health.
        // La vérification est faite dans la policy.
        return $q;
    }

    // --- Helpers ---

    public function estEnConge(): bool
    {
        return $this->statut === StatutDossierMaternite::CONGE_EN_COURS;
    }

    public function joursRestantsAvantAccouchement(): ?int
    {
        return $this->date_prevue_accouchement?->diffInDays(now(), false);
    }
}