<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anomalie relevée au calcul d'une période. Une anomalie bloquante empêche la validation.
 */
class AnomaliePaie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'anomalies_paie';

    public const UPDATED_AT = null;

    public const BLOQUANTE = 'bloquante';
    public const ALERTE = 'alerte';

    protected $fillable = ['entreprise_id', 'periode_id', 'salarie_id', 'gravite', 'code', 'message'];

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function estBloquante(): bool
    {
        return $this->gravite === self::BLOQUANTE;
    }
}
