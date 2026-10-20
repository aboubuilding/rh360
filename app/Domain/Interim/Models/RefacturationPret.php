<?php

namespace App\Domain\Interim\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RG-PMO-01 — refacturation d'un prêt : base = salaires + charges sociales + frais professionnels remboursés.
 */
class RefacturationPret extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'refacturations_prets';

    protected $fillable = [
        'entreprise_id', 'pret_main_oeuvre_id', 'periode_debut', 'periode_fin', 'salaires',
        'charges_sociales', 'frais_professionnels', 'montant_refacture', 'alerte_marge', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'salaires' => 'integer',
            'charges_sociales' => 'integer',
            'frais_professionnels' => 'integer',
            'montant_refacture' => 'integer',
        ];
    }

    public function pret(): BelongsTo
    {
        return $this->belongsTo(PretMainOeuvre::class, 'pret_main_oeuvre_id');
    }

    public function baseRefacturable(): int
    {
        return $this->salaires + $this->charges_sociales + $this->frais_professionnels;
    }
}
