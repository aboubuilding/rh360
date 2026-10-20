<?php

namespace App\Domain\Interim\Models;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Interim\Enums\StatutMission;
use App\Domain\Interim\Enums\TypeMotifMission;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Mission d'un travailleur intérimaire mis à disposition par une agence agréée
 * (Code du travail, art. 60 à 63). L'intérimaire n'est pas un salarié de l'entreprise :
 * il est hors effectif et hors paie (RG-TT-03, RG-TT-04).
 */
class MissionInterim extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'missions_interim';

    protected $fillable = [
        'entreprise_id', 'entreprise_interim_id', 'reference',
        'interimaire_nom', 'interimaire_prenoms', 'interimaire_piece', 'interimaire_telephone',
        'poste_id', 'qualification_convenue', 'travail_determine', 'type_motif', 'motif', 'titulaire_remplace_id',
        'date_debut', 'date_fin', 'date_fin_reelle', 'nombre_renouvellements',
        'salaire_convenu', 'salaire_minimum_reference', 'alerte_salaire',
        'sst_accueil_le', 'sst_epi_remis_le', 'sst_consignes_le', 'sst_valide_par', 'sst_valide_le',
        'statut', 'motif_annulation', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'type_motif' => TypeMotifMission::class,
            'statut' => StatutMission::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_fin_reelle' => 'date',
            'sst_accueil_le' => 'date',
            'sst_epi_remis_le' => 'date',
            'sst_consignes_le' => 'date',
            'sst_valide_le' => 'datetime',
            'salaire_convenu' => 'integer',
            'salaire_minimum_reference' => 'integer',
            'nombre_renouvellements' => 'integer',
        ];
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(EntrepriseInterim::class, 'entreprise_interim_id');
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_id');
    }

    public function titulaireRemplace(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'titulaire_remplace_id');
    }

    public function sstValidePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'sst_valide_par');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(FactureInterim::class, 'mission_interim_id');
    }

    public function decisions(): MorphMany
    {
        return $this->morphMany(DecisionInterim::class, 'objet')->orderBy('decide_le');
    }

    public function alertes(): MorphMany
    {
        return $this->morphMany(AlerteInterim::class, 'objet');
    }

    public function getNomInterimaireAttribute(): string
    {
        return trim($this->interimaire_nom.' '.$this->interimaire_prenoms);
    }

    // RG-TT-07 — accueil sécurité, EPI remis et consignes du poste.
    public function checklistSstComplete(): bool
    {
        return $this->sst_accueil_le && $this->sst_epi_remis_le && $this->sst_consignes_le && $this->sst_valide_le;
    }
}
