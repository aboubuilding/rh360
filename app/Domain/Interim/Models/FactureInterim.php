<?php

namespace App\Domain\Interim\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RG-TT-04 — Facture de l'agence d'intérim : seule trace financière d'une mission,
 * sans paie ni cotisations.
 */
class FactureInterim extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'factures_interim';

    protected $fillable = [
        'entreprise_id', 'entreprise_interim_id', 'mission_interim_id', 'numero_facture', 'date_facture',
        'periode_debut', 'periode_fin', 'montant', 'chemin_piece', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_facture' => 'date',
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'montant' => 'integer',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(EntrepriseInterim::class, 'entreprise_interim_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(MissionInterim::class, 'mission_interim_id');
    }
}
