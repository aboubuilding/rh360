<?php

namespace App\Domain\Performance\Models;

use App\Domain\Performance\Enums\FamilleCritere;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class CritereEvaluation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'criteres_evaluation';

    protected $fillable = [
        'entreprise_id', 'campagne_id', 'code', 'libelle',
        'famille', 'ponderation', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'ponderation' => 'decimal:2',
            'actif' => 'boolean',
            'famille' => FamilleCritere::class,
        ];
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneEvaluation::class, 'campagne_id');
    }
}