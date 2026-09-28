<?php

namespace App\Domain\Paie\Models;

use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RappelAvancement extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'rappels_avancement';

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'mouvement_id',
        'bulletin_source_id', 'periode_generation_id',
        'montant_rappel_base', 'montant_rappel_anciennete',
    ];

    protected function casts(): array
    {
        return [
            'montant_rappel_base' => 'decimal:2',
            'montant_rappel_anciennete' => 'decimal:2',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(MouvementCarriere::class, 'mouvement_id');
    }

    public function bulletinSource(): BelongsTo
    {
        return $this->belongsTo(BulletinPaie::class, 'bulletin_source_id');
    }

    public function periodeGeneration(): BelongsTo
    {
        return $this->belongsTo(PeriodePaie::class, 'periode_generation_id');
    }

    public function montantTotal(): float
    {
        return (float) ($this->montant_rappel_base + $this->montant_rappel_anciennete);
    }
}