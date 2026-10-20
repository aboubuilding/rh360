<?php

namespace App\Domain\Interim\Models;

use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RG-INT-07 — Avantage du poste occupé, jamais calculé automatiquement :
 * saisi à la main avec la référence de l'accord entre les parties et son justificatif.
 */
class AvantageInterim extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'avantages_interims';

    protected $fillable = [
        'entreprise_id', 'interim_interne_id', 'rubrique_id', 'montant_mensuel',
        'date_debut', 'date_fin', 'reference_accord', 'chemin_justificatif', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'montant_mensuel' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function interim(): BelongsTo
    {
        return $this->belongsTo(InterimInterne::class, 'interim_interne_id');
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriquePaie::class, 'rubrique_id');
    }
}
