<?php

namespace App\Domain\Organisation\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'structures';

    protected $fillable = [
        'entreprise_id', 'type_structure_id', 'parent_id',
        'code', 'nom', 'localisation', 'centre_cout', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function typeStructure(): BelongsTo
    {
        return $this->belongsTo(TypeStructure::class, 'type_structure_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Structure::class, 'parent_id');
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class, 'structure_id');
    }
}