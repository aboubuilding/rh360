<?php

namespace App\Domain\Personnel\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrouillonSalarie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'brouillons_salaries';

    protected $fillable = [
        'entreprise_id', 'utilisateur_id',
        'donnees', 'chemin_photo', 'etape_courante', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
            'etape_courante' => 'integer',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'utilisateur_id');
    }
}