<?php

namespace App\Domain\Sst\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\NatureOperationEpi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationEpi extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'operations_epi';

    protected $fillable = [
        'entreprise_id', 'dotation_id', 'cle_soumission', 'nature',
        'date_evenement', 'quantite', 'date_prochaine_verification',
        'intervenant', 'resultat', 'motif_annulation', 'cree_par',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'date_prochaine_verification' => 'date',
            'quantite' => 'integer',
            'nature' => NatureOperationEpi::class,
        ];
    }

    public function dotation(): BelongsTo
    {
        return $this->belongsTo(DotationEpi::class, 'dotation_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }
}