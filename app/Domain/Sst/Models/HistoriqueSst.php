<?php

namespace App\Domain\Sst\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueSst extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'historique_sst';

    protected $fillable = [
        'entreprise_id', 'nature', 'fiche_id', 'revision',
        'instantane', 'auteur_id',
    ];

    protected function casts(): array
    {
        return [
            'instantane' => 'array',
            'revision' => 'integer',
            'nature' => NaturePieceSst::class,
        ];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'auteur_id');
    }
}