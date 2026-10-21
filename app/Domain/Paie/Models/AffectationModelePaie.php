<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Association manuelle et datée d'un salarié à un modèle de bulletin. Elle prime sur le modèle
 * de la catégorie du salarié.
 */
class AffectationModelePaie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'affectations_modele_paie';

    protected $fillable = ['entreprise_id', 'salarie_id', 'modele_id', 'debut', 'fin', 'motif', 'cree_par'];

    protected function casts(): array
    {
        return ['debut' => 'date', 'fin' => 'date'];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function modele(): BelongsTo
    {
        return $this->belongsTo(ModelePaie::class, 'modele_id');
    }
}
