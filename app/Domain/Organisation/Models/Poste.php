<?php

namespace App\Domain\Organisation\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poste extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'postes';

    protected $fillable = [
        'entreprise_id', 'structure_id', 'code', 'intitule',
        'categorie', 'effectif_cible', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'effectif_cible' => 'integer',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }
}