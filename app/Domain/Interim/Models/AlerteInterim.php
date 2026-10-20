<?php

namespace App\Domain\Interim\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Alerte d'intérim générée par la commande interims:balayer.
 */
class AlerteInterim extends Model
{
    use BelongsToEntreprise;

    protected $table = 'alertes_interims';

    protected $fillable = [
        'entreprise_id', 'objet_type', 'objet_id', 'cle', 'niveau', 'intitule', 'message',
        'date_echeance', 'en_cours', 'cloture_le',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'en_cours' => 'boolean',
            'cloture_le' => 'datetime',
        ];
    }

    public function objet(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEnCours(Builder $q): Builder
    {
        return $q->where('en_cours', true);
    }
}
