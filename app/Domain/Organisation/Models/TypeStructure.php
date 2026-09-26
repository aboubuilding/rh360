<?php

namespace App\Domain\Organisation\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeStructure extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'types_structures';

    protected $fillable = ['entreprise_id', 'code', 'nom', 'ordre', 'actif', 'etat'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class, 'type_structure_id');
    }
}