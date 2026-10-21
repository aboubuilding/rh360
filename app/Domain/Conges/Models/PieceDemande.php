<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Justificatif déposé à l'appui d'une demande, et son contrôle par les RH.
 */
class PieceDemande extends Model
{
    use BelongsToEntreprise;

    protected $table = 'pieces_demande';

    protected $fillable = [
        'entreprise_id', 'demande_id', 'nature', 'chemin', 'nom_original', 'recue_le',
        'conforme', 'motif_non_conforme', 'controle_par', 'controle_le', 'depose_par',
    ];

    protected function casts(): array
    {
        return [
            'recue_le' => 'datetime',
            'controle_le' => 'datetime',
            'conforme' => 'boolean',
        ];
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeConge::class, 'demande_id');
    }

    public function controlePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'controle_par');
    }
}
