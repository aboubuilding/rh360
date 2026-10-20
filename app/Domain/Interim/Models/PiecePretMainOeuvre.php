<?php

namespace App\Domain\Interim\Models;

use App\Domain\Interim\Enums\TypePiecePret;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// RG-PMO-02 — pièce obligatoire d'un prêt de main-d'œuvre.
class PiecePretMainOeuvre extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'pieces_prets_main_oeuvre';

    protected $fillable = [
        'entreprise_id', 'pret_main_oeuvre_id', 'type_piece', 'date_signature',
        'chemin', 'type_mime', 'empreinte_sha256', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'type_piece' => TypePiecePret::class,
            'date_signature' => 'date',
        ];
    }

    public function pret(): BelongsTo
    {
        return $this->belongsTo(PretMainOeuvre::class, 'pret_main_oeuvre_id');
    }
}
