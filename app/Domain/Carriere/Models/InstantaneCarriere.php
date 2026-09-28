<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstantaneCarriere extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'instantanes_carriere';
    protected $primaryKey = 'mouvement_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'mouvement_id', 'entreprise_id',
        'date_effet', 'details',
    ];

    protected function casts(): array
    {
        return [
            'date_effet' => 'date',
            'details' => 'array',
        ];
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(MouvementCarriere::class, 'mouvement_id');
    }

    // --- Helpers ---

    public function getAvantAttribute(): ?array
    {
        return $this->details['avant'] ?? null;
    }

    public function getApresAttribute(): ?array
    {
        return $this->details['apres'] ?? null;
    }

    public function getNatureAttribute(): ?string
    {
        return $this->details['nature'] ?? null;
    }
}