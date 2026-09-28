<?php

namespace App\Domain\Performance\Models;

use App\Domain\Performance\Enums\StatutObjectif;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectifEvaluation extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'objectifs_evaluation';

    protected $fillable = [
        'entreprise_id', 'campagne_id', 'salarie_id',
        'intitule', 'indicateur', 'cible', 'ponderation',
        'date_echeance', 'statut', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'ponderation' => 'decimal:2',
            'date_echeance' => 'date',
            'statut' => StatutObjectif::class,
        ];
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneEvaluation::class, 'campagne_id');
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }
}