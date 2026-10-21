<?php

namespace App\Domain\Conges\Models;

use App\Domain\Conges\Enums\BasePlafond;
use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\PeriodePlafond;
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

    // Unités du délai de dépôt et du plafond, point de départ du délai de justificatif.
    public const UNITES_DELAI_DEPOT = ['calendaires' => 'Jours calendaires', 'ouvres' => 'Jours ouvrés'];

    public const UNITES_PLAFOND = ['jour' => 'Jours', 'heure' => 'Heures'];

    public const DEPARTS_JUSTIFICATION = ['evenement' => 'Date de l\'événement', 'debut_absence' => 'Premier jour d\'absence'];

    protected $table = 'types_conges';

    protected $fillable = [
        'entreprise_id', 'code', 'nom', 'categorie', 'famille', 'de_droit', 'unite',
        'droit_annuel', 'remunere', 'justificatif_requis', 'reference_legale',
        'duree_max', 'portee_duree_max',
        'traitement_salarial', 'impact_conge_annuel', 'impact_anciennete',
        'delai_justification_jours', 'delai_justification_depart', 'autorisation_prealable_requise',
        'delai_depot', 'delai_depot_unite', 'delai_information_heures',
        'plafond_valeur', 'plafond_unite', 'plafond_periode', 'plafond_base',
        'anciennete_min_mois', 'prise_continue_autorisee', 'report_credit_autorise',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'droit_annuel' => 'decimal:2',
            'duree_max' => 'decimal:2',
            'delai_justification_jours' => 'integer',
            'delai_depot' => 'integer',
            'delai_information_heures' => 'integer',
            'plafond_valeur' => 'decimal:2',
            'anciennete_min_mois' => 'integer',
            'de_droit' => 'boolean',
            'prise_continue_autorisee' => 'boolean',
            'report_credit_autorise' => 'boolean',
            'famille' => FamilleDemande::class,
            'plafond_periode' => PeriodePlafond::class,
            'plafond_base' => BasePlafond::class,
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

    public function evenements(): HasMany
    {
        return $this->hasMany(EvenementPermission::class, 'type_conge_id')->orderBy('ordre');
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

    // Absence de droit : l'ERP vérifie le justificatif, il ne décide pas de l'accorder.
    public function estDeDroit(): bool
    {
        return (bool) $this->de_droit;
    }

    public function aUnPlafond(): bool
    {
        return $this->plafond_valeur !== null && $this->plafond_periode !== null;
    }

    // Ex. « 10 jours par année civile, par enfant ».
    public function libellePlafond(): ?string
    {
        if (! $this->aUnPlafond()) {
            return null;
        }

        return trim(sprintf(
            '%s %s %s%s',
            rtrim(rtrim(number_format((float) $this->plafond_valeur, 2, ',', ' '), '0'), ','),
            mb_strtolower(self::UNITES_PLAFOND[$this->plafond_unite] ?? ''),
            mb_strtolower($this->plafond_periode->libelle()),
            $this->plafond_base === BasePlafond::ENFANT ? ', par enfant' : '',
        ));
    }
}