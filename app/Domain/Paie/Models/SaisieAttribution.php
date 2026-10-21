<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saisie-attribution des créances sur le salaire (Code art. 173 a), retenue dans la limite de
 * la quotité saisissable (Code art. 174), par rang.
 */
class SaisieAttribution extends Model
{
    use BelongsToEntreprise;

    protected $table = 'saisies_attribution';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'creancier', 'reference_acte', 'date_acte',
        'montant_total', 'rang', 'statut', 'piece', 'cree_par',
    ];

    protected function casts(): array
    {
        return ['montant_total' => 'decimal:2', 'date_acte' => 'date', 'rang' => 'integer'];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function reference(): string
    {
        return 'saisie:'.$this->id;
    }

    public function restant(?int $saufPeriodeId = null): float
    {
        $retenu = (float) LigneBulletin::withoutGlobalScopes()
            ->where('reference', $this->reference())
            ->when($saufPeriodeId, fn ($q) => $q->whereHas('bulletin', fn ($b) => $b->where('periode_id', '!=', $saufPeriodeId)))
            ->sum('montant');

        return max(0, round((float) $this->montant_total - $retenu, 2));
    }
}
