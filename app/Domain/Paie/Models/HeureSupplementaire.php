<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeureSupplementaire extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'heures_supplementaires';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'reference',
        'debut_travail', 'fin_travail',
        'periode_paiement_id', 'periode_origine_id',
        'heures_hs20', 'heures_hs40',
        'heures_hs65_jour', 'heures_hs65_nuit', 'heures_hs100',
        'salaire_base_fige', 'sursalaire_fige', 'taux_horaire',
        'est_rappel', 'motif', 'motif_retard', 'cree_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'debut_travail' => 'date',
            'fin_travail' => 'date',
            'heures_hs20' => 'decimal:2',
            'heures_hs40' => 'decimal:2',
            'heures_hs65_jour' => 'decimal:2',
            'heures_hs65_nuit' => 'decimal:2',
            'heures_hs100' => 'decimal:2',
            'salaire_base_fige' => 'decimal:2',
            'sursalaire_fige' => 'decimal:2',
            'taux_horaire' => 'decimal:4',
            'est_rappel' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function periodePaiement(): BelongsTo
    {
        return $this->belongsTo(PeriodePaie::class, 'periode_paiement_id');
    }

    public function periodeOrigine(): BelongsTo
    {
        return $this->belongsTo(PeriodePaie::class, 'periode_origine_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    // --- Scopes ---

    public function scopePourPeriode(Builder $q, int $periodeId): Builder
    {
        return $q->where('periode_paiement_id', $periodeId);
    }

    // --- Helpers ---

    public function totalHeures(): float
    {
        return (float) (
            $this->heures_hs20
            + $this->heures_hs40
            + $this->heures_hs65_jour
            + $this->heures_hs65_nuit
            + $this->heures_hs100
        );
    }

    public function montantTotal(): float
    {
        $taux = (float) $this->taux_horaire;

        return round(
            ($this->heures_hs20 * $taux * 1.20)
            + ($this->heures_hs40 * $taux * 1.40)
            + ($this->heures_hs65_jour * $taux * 1.65)
            + ($this->heures_hs65_nuit * $taux * 1.65)
            + ($this->heures_hs100 * $taux * 2.00),
            2
        );
    }
}