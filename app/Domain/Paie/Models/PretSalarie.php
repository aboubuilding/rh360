<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prêt ou avance consenti par l'employeur, remboursé par cession volontaire souscrite par le
 * salarié et communiquée pour vérification à l'inspecteur du travail (avance de l'employeur) ou
 * devant le président du tribunal (autres cas) (Code art. 173 b).
 */
class PretSalarie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'prets_salaries';

    public const TYPES = ['avance' => 'Avance sur salaire', 'pret' => 'Prêt'];

    public const VERIFICATIONS = ['inspecteur' => 'Inspecteur du travail', 'tribunal' => 'Président du tribunal'];

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'type', 'montant', 'echeance', 'premier_mois',
        'verification', 'verifie_le', 'piece', 'statut', 'motif', 'cree_par',
    ];

    protected function casts(): array
    {
        return ['montant' => 'decimal:2', 'echeance' => 'decimal:2', 'premier_mois' => 'date', 'verifie_le' => 'date'];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function reference(): string
    {
        return 'pret:'.$this->id;
    }

    // Remboursé sur les bulletins des autres périodes.
    public function rembourse(?int $saufPeriodeId = null): float
    {
        return (float) LigneBulletin::withoutGlobalScopes()
            ->where('reference', $this->reference())
            ->when($saufPeriodeId, fn ($q) => $q->whereHas('bulletin', fn ($b) => $b->where('periode_id', '!=', $saufPeriodeId)))
            ->sum('montant');
    }

    public function restant(?int $saufPeriodeId = null): float
    {
        return max(0, round((float) $this->montant - $this->rembourse($saufPeriodeId), 2));
    }
}
