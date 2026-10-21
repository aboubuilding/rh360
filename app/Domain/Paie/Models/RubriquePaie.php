<?php

namespace App\Domain\Paie\Models;

use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubriquePaie extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'rubriques_paie';

    protected $fillable = [
        'entreprise_id', 'code', 'nom', 'description',
        'nature', 'type_retenue', 'recurrence', 'mode_calcul',
        'taux', 'montant_defaut',
        'imposable', 'traitement_fiscal', 'pourcentage_imposable',
        'methode_evaluation', 'justificatif_requis', 'reference_fiscale',
        'soumis_cotisation', 'est_frais', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'taux' => 'decimal:4',
            'montant_defaut' => 'decimal:2',
            'pourcentage_imposable' => 'decimal:4',
            'imposable' => 'boolean',
            'justificatif_requis' => 'boolean',
            'soumis_cotisation' => 'boolean',
            'est_frais' => 'boolean',
            'actif' => 'boolean',
            'nature' => NatureRubrique::class,
            'type_retenue' => \App\Domain\Paie\Enums\TypeRetenue::class,
            'recurrence' => RecurrenceRubrique::class,
            'mode_calcul' => ModeCalculRubrique::class,
            'traitement_fiscal' => TraitementFiscal::class,
        ];
    }

    // --- Relations ---

    public function elementsFixes(): HasMany
    {
        return $this->hasMany(ElementPaieSalarie::class, 'rubrique_id');
    }

    public function saisies(): HasMany
    {
        return $this->hasMany(SaisiePaie::class, 'rubrique_id');
    }

    public function lignesBulletins(): HasMany
    {
        return $this->hasMany(LigneBulletin::class, 'rubrique_id');
    }

    public function modeles(): HasMany
    {
        return $this->hasMany(ModelePaieRubrique::class, 'rubrique_id');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('code', 'like', "%{$terme}%")
              ->orWhere('nom', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeNature(Builder $q, ?string $nature): Builder
    {
        return $q->when($nature, fn ($q) => $q->where('nature', $nature));
    }

    public function scopeActives(Builder $q): Builder
    {
        return $q->where('actif', true);
    }

    // --- Helpers ---

    public function estGain(): bool
    {
        return $this->nature === NatureRubrique::GAIN;
    }

    public function estRetenue(): bool
    {
        return in_array($this->nature, [NatureRubrique::RETENUE, NatureRubrique::COTISATION], true);
    }

    public function estFixe(): bool
    {
        return $this->recurrence === RecurrenceRubrique::FIXE;
    }

    public function estVariable(): bool
    {
        return $this->recurrence === RecurrenceRubrique::VARIABLE;
    }

    // Rubriques qui neutralisent le salaire de jours non payés ou remplacés (montant négatif sur le brut).
    public const NEUTRALISATIONS = ['ABS-RET', 'CONGE-RET', 'MAL-RET', 'MAT-RET'];

    /**
     * Effet de la rubrique sur le salaire, affiché avant de l'ajouter à un modèle ou à un bulletin.
     *
     * @return array{signe: string, libelle: string, couleur: string}
     */
    public function effetSalaire(): array
    {
        return match (true) {
            in_array($this->code, self::NEUTRALISATIONS, true) => ['signe' => '−', 'libelle' => 'Retranche du salaire brut (jours non payés ou remplacés)', 'couleur' => 'danger'],
            $this->nature === NatureRubrique::GAIN && $this->est_frais => ['signe' => '+', 'libelle' => 'Ajoutée au net à payer, hors brut et hors cotisations (remboursement de frais)', 'couleur' => 'info'],
            $this->nature === NatureRubrique::GAIN => ['signe' => '+', 'libelle' => 'Ajoute au salaire brut', 'couleur' => 'success'],
            $this->nature === NatureRubrique::COTISATION => ['signe' => '−', 'libelle' => 'Retranche du salaire (cotisation salariale)', 'couleur' => 'warning'],
            $this->nature === NatureRubrique::COTISATION_PATRONALE => ['signe' => '0', 'libelle' => 'À la charge de l\'employeur, ne change pas le salaire', 'couleur' => 'secondary'],
            default => ['signe' => '−', 'libelle' => 'Retranche du net à payer (retenue)', 'couleur' => 'danger'],
        };
    }

    // « Effet » du formulaire : gain, remboursement de frais, retenue ou cotisation.
    public function effet(): string
    {
        return match (true) {
            $this->nature === NatureRubrique::GAIN && $this->est_frais => 'frais',
            default => $this->nature?->value ?? 'gain',
        };
    }
}
