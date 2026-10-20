<?php

namespace App\Domain\Interim\Models;

use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Enums\ReponseSalarie;
use App\Domain\Interim\Enums\StatutPret;
use App\Domain\Interim\Enums\TypePiecePret;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Prêt de main-d'œuvre à but non lucratif (Code du travail, art. 8). Le contrat n'est ni rompu
 * ni suspendu : le salarié reste dans l'effectif et la paie de l'entreprise prêteuse (RG-PMO-04).
 */
class PretMainOeuvre extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'prets_main_oeuvre';

    protected $fillable = [
        'entreprise_id', 'reference', 'salarie_id', 'mouvement_carriere_id',
        'utilisatrice_raison_sociale', 'utilisatrice_adresse', 'utilisatrice_contact',
        'date_debut', 'date_fin', 'qualification', 'mode_calcul_refacturation',
        'travail_confie', 'horaires', 'lieu', 'caracteristiques_poste',
        'reponse_salarie', 'date_reponse_salarie',
        'poste_origine_id', 'affectation_origine_id', 'position_origine_id', 'salaire_base_origine',
        'date_retour', 'poste_retour_id', 'statut', 'motif_annulation', 'cree_par', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_reponse_salarie' => 'date',
            'date_retour' => 'date',
            'reponse_salarie' => ReponseSalarie::class,
            'statut' => StatutPret::class,
            'salaire_base_origine' => 'integer',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function mouvementCarriere(): BelongsTo
    {
        return $this->belongsTo(MouvementCarriere::class, 'mouvement_carriere_id');
    }

    public function posteOrigine(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_origine_id');
    }

    public function posteRetour(): BelongsTo
    {
        return $this->belongsTo(Poste::class, 'poste_retour_id');
    }

    public function positionOrigine(): BelongsTo
    {
        return $this->belongsTo(PositionClassification::class, 'position_origine_id');
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(PiecePretMainOeuvre::class, 'pret_main_oeuvre_id');
    }

    public function refacturations(): HasMany
    {
        return $this->hasMany(RefacturationPret::class, 'pret_main_oeuvre_id');
    }

    public function decisions(): MorphMany
    {
        return $this->morphMany(DecisionInterim::class, 'objet')->orderBy('decide_le');
    }

    public function alertes(): MorphMany
    {
        return $this->morphMany(AlerteInterim::class, 'objet');
    }

    /**
     * RG-PMO-02 — pièces obligatoires manquantes.
     *
     * @return list<TypePiecePret>
     */
    public function piecesManquantes(): array
    {
        $presentes = $this->pieces()->get()->map(fn ($p) => $p->type_piece)->all();

        return array_values(array_filter(TypePiecePret::cases(), fn ($t) => ! in_array($t, $presentes, true)));
    }
}
