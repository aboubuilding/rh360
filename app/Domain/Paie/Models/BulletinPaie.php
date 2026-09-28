<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulletinPaie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'bulletins_paie';

    protected $fillable = [
        'entreprise_id', 'periode_id', 'salarie_id',
        'montant_brut', 'montant_retenues', 'montant_net',
        'brut_imposable', 'retenues_sociales_deductibles',
        'abattement_professionnel', 'deduction_charges_famille',
        'base_imposable', 'montant_irpp', 'calcule_le',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'montant_brut' => 'decimal:2',
            'montant_retenues' => 'decimal:2',
            'montant_net' => 'decimal:2',
            'brut_imposable' => 'decimal:2',
            'retenues_sociales_deductibles' => 'decimal:2',
            'abattement_professionnel' => 'decimal:2',
            'deduction_charges_famille' => 'decimal:2',
            'base_imposable' => 'decimal:2',
            'montant_irpp' => 'decimal:2',
            'calcule_le' => 'datetime',
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

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneBulletin::class, 'bulletin_id')->orderBy('ordre');
    }

    public function getMontantNetAPayerAttribute(): float
    {
        return (float) $this->montant_net;
    }
}