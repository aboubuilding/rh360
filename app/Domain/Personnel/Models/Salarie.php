<?php

namespace App\Domain\Personnel\Models;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salarie extends Model
{
    use HasFactory, BelongsToEntreprise;
    use AvecEtat {
        estActif as protected estActifSelonEtat;
    }

    protected $table = 'salaries';

    /**
     * Familles de données sensibles (CDC §6) : sans la permission, ces champs
     * ne sont ni affichés, ni modifiables, ni exportés.
     */
    public const CHAMPS_SENSIBLES = [
        'sensitive.social_health' => ['numero_cnss', 'date_immatriculation_cnss', 'numero_amu', 'organisme_assurance'],
        'sensitive.banking' => ['banque', 'compte_bancaire', 'mode_paiement'],
        'sensitive.gps' => ['gps_latitude', 'gps_longitude'],
    ];

    /**
     * Champs sensibles que l'utilisateur n'a pas le droit de lire ni d'écrire.
     *
     * @return list<string>
     */
    public static function champsSensiblesInterdits(\App\Domain\Administration\Models\Utilisateur $utilisateur): array
    {
        return collect(self::CHAMPS_SENSIBLES)
            ->reject(fn (array $champs, string $permission) => $utilisateur->peut($permission))
            ->flatten()
            ->values()
            ->all();
    }

    protected $fillable = [
        'entreprise_id', 'numero_enregistrement', 'matricule',
        'nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance', 'nationalite',
        'chemin_photo', 'type_piece', 'numero_piece', 'date_expiration_piece',
        'telephone_principal', 'telephone_secondaire',
        'email_personnel', 'email_professionnel',
        'adresse', 'ville', 'pays_residence',
        'gps_latitude', 'gps_longitude',
        'situation_matrimoniale',
        'contact_urgence_nom', 'contact_urgence_lien', 'contact_urgence_telephone',
        'numero_cnss', 'date_immatriculation_cnss',
        'numero_amu', 'organisme_assurance',
        'banque', 'compte_bancaire', 'mode_paiement',
        'date_embauche', 'type_contrat', 'reference_contrat', 'date_contrat',
        'date_fin_contrat', 'date_prise_service', 'date_fin_essai',
        'origine_carriere', 'lieu_affectation', 'statut_emploi', 'statut_dossier',
        'fusionne_dans_salarie_id', 'motif_fusion', 'fusionne_le',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_expiration_piece' => 'date',
            'date_immatriculation_cnss' => 'date',
            'date_embauche' => 'date',
            'date_contrat' => 'date',
            'date_fin_contrat' => 'date',
            'date_prise_service' => 'date',
            'date_fin_essai' => 'date',
            'fusionne_le' => 'datetime',
            'gps_latitude' => 'decimal:7',
            'gps_longitude' => 'decimal:7',
            'actif' => 'boolean',
            'statut_dossier' => StatutDossier::class,
            'statut_emploi' => StatutEmploi::class,
        ];
    }

    // --- Relations ---

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'salarie_id');
    }

    public function affectationCourante(): BelongsTo
    {
        return $this->belongsTo(Affectation::class, 'id', 'salarie_id')
            ->where('affectations.en_cours', true);
    }

    public function membresFoyer(): HasMany
    {
        return $this->hasMany(MembreFoyer::class, 'salarie_id');
    }

    /**
     * Alias utilisé par la liaison de route imbriquée {salarie}/foyer/{membre}
     * (scopeBindings déduit la relation du nom du paramètre : « membres »).
     */
    public function membres(): HasMany
    {
        return $this->membresFoyer();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentSalarie::class, 'salarie_id');
    }

    public function salarieAbsorbant(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'fusionne_dans_salarie_id');
    }

    // --- Scopes ---

    public function scopeRecherche(Builder $q, ?string $terme): Builder
    {
        return $q->when($terme, fn ($q) => $q->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
              ->orWhere('prenoms', 'like', "%{$terme}%")
              ->orWhere('matricule', 'like', "%{$terme}%")
              ->orWhere('numero_enregistrement', 'like', "%{$terme}%");
        }));
    }

    public function scopeDeStructure(Builder $q, ?int $structureId): Builder
    {
        return $q->when($structureId, fn ($q) => $q->whereHas('affectations', function ($q) use ($structureId) {
            $q->where('structure_id', $structureId)->where('en_cours', true);
        }));
    }

    public function scopeParSituation(Builder $q, ?string $situation): Builder
    {
        return match ($situation) {
            'actifs'   => $q->where('actif', true)->where('statut_emploi', StatutEmploi::ACTIF->value),
            'anciens'  => $q->where('actif', false),
            default    => $q,
        };
    }

    public function scopeParCompletude(Builder $q, ?string $completude): Builder
    {
        return match ($completude) {
            'complets'    => $q->where('statut_dossier', StatutDossier::COMPLET->value),
            'incomplets'  => $q->where('statut_dossier', StatutDossier::INCOMPLET->value),
            default       => $q,
        };
    }

    // --- Accessors ---

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenoms);
    }

    public function getEstFusionneAttribute(): bool
    {
        return ! is_null($this->fusionne_dans_salarie_id);
    }

    public function estActif(): bool
    {
        return $this->estActifSelonEtat() && $this->actif;
    }

    public function getInitialesAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->nom, 0, 1) . mb_substr($this->prenoms, 0, 1));
    }
}