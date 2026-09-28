<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegleIrpp extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'regles_irpp';

    protected $fillable = [
        'entreprise_id', 'debut_effet', 'fin_effet', 'reference_legale',
        'taux_abattement_professionnel', 'plafond_abattement_professionnel',
        'deduction_mensuelle_par_charge', 'nombre_max_charges',
        'tranches', 'taux_tranches',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'debut_effet' => 'date',
            'fin_effet' => 'date',
            'taux_abattement_professionnel' => 'decimal:4',
            'plafond_abattement_professionnel' => 'decimal:2',
            'deduction_mensuelle_par_charge' => 'decimal:2',
            'nombre_max_charges' => 'integer',
            'tranches' => 'array',
            'taux_tranches' => 'array',
            'actif' => 'boolean',
        ];
    }

    public function scopeEnVigueur(Builder $q, $date): Builder
    {
        return $q->where('debut_effet', '<=', $date)
                 ->where(function ($q) use ($date) {
                     $q->whereNull('fin_effet')->orWhere('fin_effet', '>=', $date);
                 })
                 ->where('actif', true)
                 ->orderByDesc('debut_effet');
    }

    /**
     * Calcule l'IRPP progressif sur une base imposable.
     */
    public function calculerIrpp(float $baseImposable): float
    {
        $tranches = $this->tranches ?? [];
        $taux = $this->taux_tranches ?? [];

        if (empty($tranches) || count($tranches) !== count($taux)) {
            return 0.0;
        }

        $impot = 0.0;
        $plafondPrecedent = 0;

        foreach ($tranches as $i => $plafond) {
            $tauxTranche = (float) $taux[$i] / 100; // taux en %
            $plafond = (float) $plafond;

            if ($baseImposable <= $plafondPrecedent) break;

            $assiette = min($baseImposable, $plafond) - $plafondPrecedent;
            $impot += $assiette * $tauxTranche;

            $plafondPrecedent = $plafond;

            if ($baseImposable <= $plafond) break;
        }

        return round($impot, 2);
    }

    /**
     * Calcule l'abattement professionnel sur un brut imposable.
     */
    public function calculerAbattement(float $brutImposable): float
    {
        $abattement = $brutImposable * ((float) $this->taux_abattement_professionnel / 100);
        return round(min($abattement, (float) $this->plafond_abattement_professionnel), 2);
    }

    /**
     * Calcule la déduction pour charges de famille.
     */
    public function calculerDeductionCharges(int $nombreCharges): float
    {
        $charges = min($nombreCharges, $this->nombre_max_charges);
        return round($charges * (float) $this->deduction_mensuelle_par_charge, 2);
    }
}