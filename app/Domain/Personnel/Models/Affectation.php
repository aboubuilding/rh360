<?php

namespace App\Domain\Personnel\Models;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'affectations';

    protected $fillable = [
        'salarie_id', 'structure_id', 'poste_id',
        'date_debut', 'date_fin', 'en_cours', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'en_cours' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_id');
    }
}