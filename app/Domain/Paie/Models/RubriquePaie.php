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
        'nature', 'recurrence', 'mode_calcul',
        'taux', 'montant_defaut',
        'imposable', 'traitement_fiscal', 'pourcentage_imposable',
        'methode_evaluation', 'justificatif_requis', 'reference_fiscale',
        'soumis_cotisation', 'actif', 'etat',
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
            'actif' => 'boolean',
            'nature' => NatureRubrique::class,
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
}