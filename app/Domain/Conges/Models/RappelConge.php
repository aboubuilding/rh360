<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rappel d'un salarié pendant son congé : le congé est prolongé des jours travaillés.
 */
class RappelConge extends Model
{
    use BelongsToEntreprise;

    protected $table = 'rappels_conge';

    protected $fillable = ['entreprise_id', 'demande_id', 'du', 'au', 'jours', 'motif', 'reprise_avant', 'reprise_apres', 'ordonne_par'];

    protected function casts(): array
    {
        return [
            'du' => 'date',
            'au' => 'date',
            'reprise_avant' => 'date',
            'reprise_apres' => 'date',
            'jours' => 'decimal:2',
        ];
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeConge::class, 'demande_id');
    }
}
