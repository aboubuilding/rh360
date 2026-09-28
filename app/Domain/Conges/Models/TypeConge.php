<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeConge extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'types_conges';

    protected $fillable = [
        'entreprise_id', 'code', 'nom', 'categorie', 'unite',
        'droit_annuel', 'remunere', 'justificatif_requis', 'reference_legale',
        'duree_max', 'portee_duree_max',
        'traitement_salarial', 'impact_conge_annuel', 'impact_anciennete',
        'delai_justification_jours', 'autorisation_prealable_requise',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'droit_annuel' => 'decimal:2',
            'duree_max' => 'decimal:2',
            'delai_justification_jours' => 'integer',
            'remunere' => 'boolean',
            'justificatif_requis' => 'boolean',
            'autorisation_prealable_requise' => 'boolean',
            'actif' => 'boolean',
            'categorie' => CategorieTypeConge::class,
            'unite' => UniteConge::class,
            'traitement_salarial' => TraitementSalarial::class,
            'impact_conge_annuel' => ImpactCongeAnnuel::class,
            'impact_anciennete' => ImpactAnciennete::class,
        ];
    }

    // --- Relations ---

    public function soldes(): HasMany
    {
        return $this->hasMany(SoldeConge::class, 'type_conge_id');
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeConge::class, 'type_conge_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class, 'type_conge_id');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('code', 'like', "%{$terme}%")
              ->orWhere('nom', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeCategorie(Builder $q, ?string $categorie): Builder
    {
        return $q->when($categorie, fn ($q) => $q->where('categorie', $categorie));
    }

    // --- Helpers ---

    public function estConge(): bool
    {
        return $this->categorie === CategorieTypeConge::CONGE;
    }

    public function estPermission(): bool
    {
        return $this->categorie === CategorieTypeConge::PERMISSION;
    }

    public function estAbsence(): bool
    {
        return $this->categorie === CategorieTypeConge::ABSENCE;
    }

    public function necessiteJustificatif(): bool
    {
        return (bool) $this->justificatif_requis;
    }

    public function necessiteAutorisation(): bool
    {
        return (bool) $this->autorisation_prealable_requise;
    }
}