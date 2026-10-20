<?php

namespace App\Domain\Interim\Models;

use App\Domain\Interim\Enums\StatutAgrement;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entreprise de travail temporaire (agence d'intérim) — seul employeur de l'intérimaire.
 */
class EntrepriseInterim extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'entreprises_interim';

    protected $fillable = [
        'entreprise_id', 'raison_sociale', 'numero_agrement', 'date_agrement',
        'date_fin_validite_agrement', 'statut_agrement', 'adresse', 'contact',
        'telephone', 'email', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_agrement' => 'date',
            'date_fin_validite_agrement' => 'date',
            'statut_agrement' => StatutAgrement::class,
        ];
    }

    public function missions(): HasMany
    {
        return $this->hasMany(MissionInterim::class, 'entreprise_interim_id');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(FactureInterim::class, 'entreprise_interim_id');
    }

    /**
     * RG-TT-01 — Agrément du ministre chargé du travail, valide sur toute la période :
     * numéro et date d'agrément renseignés, agrément ni suspendu ni retiré, obtenu avant le
     * début et non expiré à la fin.
     */
    public function agrementValideSur(CarbonInterface $debut, CarbonInterface $fin): bool
    {
        return $this->numero_agrement
            && $this->date_agrement
            && $this->statut_agrement === StatutAgrement::VALIDE
            && $this->date_agrement->lte($debut)
            && (! $this->date_fin_validite_agrement || $this->date_fin_validite_agrement->gte($fin));
    }
}
