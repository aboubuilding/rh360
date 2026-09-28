<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneBulletin extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'lignes_bulletins';

    protected $fillable = [
        'entreprise_id', 'bulletin_id', 'rubrique_id',
        'code', 'libelle', 'nature',
        'montant', 'traitement_fiscal', 'montant_imposable', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'montant_imposable' => 'decimal:2',
            'ordre' => 'integer',
        ];
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(BulletinPaie::class, 'bulletin_id');
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriquePaie::class, 'rubrique_id');
    }
}