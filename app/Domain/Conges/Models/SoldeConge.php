<?php

namespace App\Domain\Conges\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoldeConge extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'soldes_conges';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'type_conge_id', 'annee',
        'solde_ouverture', 'acquis', 'ajustement', 'consomme', 'reserve',
        'observations', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'annee' => 'integer',
            'solde_ouverture' => 'decimal:2',
            'acquis' => 'decimal:2',
            'ajustement' => 'decimal:2',
            'consomme' => 'decimal:2',
            'reserve' => 'decimal:2',
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }

    // --- Scopes ---

    public function scopePourAnnee(Builder $q, int $annee): Builder
    {
        return $q->where('annee', $annee);
    }

    public function scopePourSalarie(Builder $q, int $salarieId): Builder
    {
        return $q->where('salarie_id', $salarieId);
    }

    // --- Accessors / Helpers ---

    /**
     * Solde disponible = (ouverture + acquis + ajustement) - (consommé + réservé)
     */
    public function getDisponibleAttribute(): float
    {
        return (float) (
            $this->solde_ouverture
            + $this->acquis
            + $this->ajustement
            - $this->consomme
            - $this->reserve
        );
    }

    public function getTotalAcquisAttribute(): float
    {
        return (float) ($this->solde_ouverture + $this->acquis + $this->ajustement);
    }

    public function estSuffisant(float $joursDemandes): bool
    {
        return $this->disponible >= $joursDemandes;
    }
}