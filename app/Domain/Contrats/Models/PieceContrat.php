<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceContrat extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'pieces_contrats';

    protected $fillable = [
        'entreprise_id', 'contrat_id', 'objet', 'libelle',
        'chemin', 'type_mime', 'empreinte_sha256', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'objet' => ObjetPieceContrat::class,
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }
}