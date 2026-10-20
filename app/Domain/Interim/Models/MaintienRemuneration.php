<?php

namespace App\Domain\Interim\Models;

use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Enums\MotifMaintienRemuneration;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RG-INF-01 à 03 — salaire (et classement) maintenus pendant une affectation à un emploi
 * inférieur ou la mutation d'une salariée enceinte.
 */
class MaintienRemuneration extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'maintiens_remuneration';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'mouvement_carriere_id', 'motif', 'salaire_base_maintenu',
        'position_maintenue_id', 'date_debut', 'date_fin', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'motif' => MotifMaintienRemuneration::class,
            'salaire_base_maintenu' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(MouvementCarriere::class, 'mouvement_carriere_id');
    }

    public function positionMaintenue(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_maintenue_id');
    }

    public function scopeEnVigueurLe(Builder $q, CarbonInterface $date): Builder
    {
        return $q->where('date_debut', '<=', $date)
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', $date));
    }
}
