<?php

namespace App\Domain\Sst\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceJointeSst extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'pieces_jointes_sst';

    protected $fillable = [
        'entreprise_id', 'nature', 'fiche_id', 'cle_soumission',
        'libelle', 'chemin', 'type_mime', 'auteur_id',
    ];

    protected function casts(): array
    {
        return ['nature' => NaturePieceSst::class];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'auteur_id');
    }

    /**
     * Relation dynamique vers la fiche parente selon la nature.
     */
    public function fiche()
    {
        return match ($this->nature) {
            NaturePieceSst::VISITE_MEDICALE    => VisiteMedicale::find($this->fiche_id),
            NaturePieceSst::EVENEMENT_SECURITE => EvenementSecurite::find($this->fiche_id),
            NaturePieceSst::RISQUE             => Risque::find($this->fiche_id),
            NaturePieceSst::DOTATION_EPI       => DotationEpi::find($this->fiche_id),
            NaturePieceSst::HABILITATION       => Habilitation::find($this->fiche_id),
            default                            => null,
        };
    }
}