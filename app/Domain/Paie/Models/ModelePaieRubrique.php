<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelePaieRubrique extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'modeles_paie_rubriques';

    protected $fillable = [
        'entreprise_id', 'modele_id', 'rubrique_id',
        'montant_defaut', 'obligatoire', 'ordre', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'montant_defaut' => 'decimal:2',
            'obligatoire' => 'boolean',
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function modele(): BelongsTo
    {
        return $this->belongsTo(ModelePaie::class, 'modele_id');
    }

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriquePaie::class, 'rubrique_id');
    }
}