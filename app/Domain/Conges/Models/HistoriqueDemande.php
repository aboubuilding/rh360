<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Changement de statut d'une demande. Historique immuable : une ligne n'est jamais modifiée
 * ni supprimée.
 */
class HistoriqueDemande extends Model
{
    use BelongsToEntreprise;

    protected $table = 'historique_demandes';

    public const UPDATED_AT = null;

    protected $fillable = ['entreprise_id', 'demande_id', 'de_statut', 'vers_statut', 'automatique', 'acteur_id', 'motif'];

    protected function casts(): array
    {
        return [
            'de_statut' => StatutDemandeConge::class,
            'vers_statut' => StatutDemandeConge::class,
            'automatique' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('L\'historique d\'une demande ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('L\'historique d\'une demande ne peut pas être supprimé.'));
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'acteur_id');
    }
}
