<?php

namespace App\Domain\Sst\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use App\Domain\Sst\Enums\StatutActionSecurite;
use App\Domain\Sst\Enums\TypeMesurePrevention;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionRisque extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'actions_risques';

    protected $fillable = [
        'entreprise_id', 'risque_id', 'cle_soumission',
        'intitule', 'type_mesure', 'responsable_salarie_id',
        'date_echeance', 'statut', 'date_realisation', 'resultat',
        'motif_annulation', 'cree_par', 'modifie_par', 'revision',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'date_realisation' => 'date',
            'revision' => 'integer',
            'statut' => StatutActionSecurite::class,
            'type_mesure' => TypeMesurePrevention::class,
        ];
    }

    public function risque(): BelongsTo
    {
        return $this->belongsTo(Risque::class, 'risque_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'responsable_salarie_id');
    }

    public function estEnRetard(): bool
    {
        return $this->statut !== StatutActionSecurite::REALISEE
            && $this->date_echeance
            && $this->date_echeance->isPast();
    }
}