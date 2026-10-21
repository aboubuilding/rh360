<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Distinction honorifique : diplôme et gratification à un palier d'ancienneté de service.
 * Statut : « due », puis « versee » une fois la gratification portée en paie.
 */
class DistinctionHonorifique extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'distinctions_honorifiques';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'palier_annees', 'date_anniversaire', 'mois', 'base',
        'prime_anciennete', 'montant', 'statut', 'periode_paie_id', 'decide_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_anniversaire' => 'date',
            'mois' => 'float',
            'base' => 'integer',
            'prime_anciennete' => 'integer',
            'montant' => 'integer',
            'palier_annees' => 'integer',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function periodePaie(): BelongsTo
    {
        return $this->belongsTo(PeriodePaie::class, 'periode_paie_id');
    }
}
