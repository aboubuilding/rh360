<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SituationCarriere extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'situations_carriere';

    protected $fillable = [
        'entreprise_id', 'salarie_id',
        'position_classification_id', 'position_ouverture_id',
        'date_reference_ouverture',
        'date_effet_categorie', 'date_effet_classe', 'date_effet_echelon',
        'date_reference_avancement',
        'type_source', 'categorie_source', 'reference_source',
        'reference_acte', 'date_acte', 'chemin_justificatif',
        'observations', 'reference_lot_reprise',
        'statut_historique', 'statut_fiabilite',
        'enregistre_par', 'enregistre_le',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_reference_ouverture'   => 'date',
            'date_effet_categorie'       => 'date',
            'date_effet_classe'          => 'date',
            'date_effet_echelon'         => 'date',
            'date_reference_avancement'  => 'date',
            'date_acte'                  => 'date',
            'enregistre_le'              => 'datetime',
            'type_source'                => TypeSourceSituation::class,
            'statut_historique'          => StatutHistorique::class,
            'statut_fiabilite'           => StatutFiabilite::class,
        ];
    }

    // --- Relations ---

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function positionClassification(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_classification_id');
    }

    public function positionOuverture(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_ouverture_id');
    }

    public function enregistrePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'enregistre_par');
    }

    // --- Accessors ---

    public function libelleComplet(): string
    {
        return $this->positionClassification?->libelleComplet() ?? '—';
    }

    public function ancienneteAnnees(): int
    {
        if (! $this->date_reference_avancement) return 0;
        return $this->date_reference_avancement->diffInYears(now());
    }

    public function joursDepuisEffetEchelon(): ?int
    {
        if (! $this->date_effet_echelon) return null;
        return $this->date_effet_echelon->diffInDays(now());
    }

    public function estFiable(): bool
    {
        return $this->statut_fiabilite === StatutFiabilite::CONFIRME;
    }
}