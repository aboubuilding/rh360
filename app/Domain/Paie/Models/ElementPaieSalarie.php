<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementPaieSalarie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'elements_paie_salaries';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'rubrique_id',
        'montant', 'debut', 'fin', 'exclu', 'accord_le', 'actif', 'observations', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'debut' => 'date',
            'fin' => 'date',
            'exclu' => 'boolean',
            'accord_le' => 'date',
            'actif' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriquePaie::class, 'rubrique_id');
    }

    // Élément en vigueur sur au moins un jour de l'intervalle (sans dates : toujours en vigueur).
    public function enVigueurEntre(\Carbon\CarbonInterface $du, \Carbon\CarbonInterface $au): bool
    {
        return (! $this->debut || $this->debut->lte($au)) && (! $this->fin || $this->fin->gte($du));
    }
}