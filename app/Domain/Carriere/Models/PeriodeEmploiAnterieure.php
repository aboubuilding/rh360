<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Carriere\Enums\MotifDepart;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Emploi antérieur du salarié dans l'entreprise, avant sa réembauche.
 */
class PeriodeEmploiAnterieure extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'periodes_emploi_anterieures';

    protected $fillable = ['entreprise_id', 'salarie_id', 'date_debut', 'date_fin', 'motif_depart', 'chemin_piece', 'cree_par', 'etat'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'motif_depart' => MotifDepart::class,
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }
}
