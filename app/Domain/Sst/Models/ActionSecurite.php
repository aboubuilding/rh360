<?php

namespace App\Domain\Sst\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\StatutActionSecurite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionSecurite extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'actions_securite';

    protected $fillable = [
        'entreprise_id', 'evenement_id', 'cle_soumission',
        'intitule', 'responsable_salarie_id', 'date_echeance',
        'statut', 'date_realisation', 'resultat', 'motif_annulation',
        'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'date_realisation' => 'date',
            'revision' => 'integer',
            'statut' => StatutActionSecurite::class,
        ];
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(EvenementSecurite::class, 'evenement_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'responsable_salarie_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cree_par');
    }

    public function estEnRetard(): bool
    {
        return $this->statut !== StatutActionSecurite::REALISEE
            && $this->date_echeance
            && $this->date_echeance->isPast();
    }
}