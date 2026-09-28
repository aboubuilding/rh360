<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaisiePaie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'saisies_paie';

    protected $fillable = [
        'entreprise_id', 'periode_id', 'salarie_id', 'rubrique_id',
        'quantite', 'taux', 'montant', 'observations',
        'cree_par', 'modifie_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:2',
            'taux' => 'decimal:4',
            'montant' => 'decimal:2',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePaie::class, 'periode_id');
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriquePaie::class, 'rubrique_id');
    }
}