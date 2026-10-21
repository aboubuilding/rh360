<?php

namespace App\Domain\Paie\Models;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sortie d'un salarié : base du bulletin de sortie (solde de tout compte), payé dès la cessation
 * du service (Code art. 165).
 */
class SortieSalarie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'sorties_salaries';

    public const MOTIFS = [
        'licenciement_personnel' => 'Licenciement pour motif personnel',
        'licenciement_economique' => 'Licenciement pour motif économique',
        'demission' => 'Démission',
        'rupture_conventionnelle' => 'Rupture conventionnelle',
        'fin_cdd' => 'Fin de contrat à durée déterminée',
        'fin_projet' => 'Fin de contrat de projet',
        'retraite' => 'Départ à la retraite',
        'deces' => 'Décès',
    ];

    public const PREAVIS = [
        'effectue' => 'Préavis effectué',
        'dispense_employeur' => 'Dispense décidée par l\'employeur',
        'dispense_salarie' => 'Dispense demandée par le salarié et acceptée',
        'sans_objet' => 'Sans objet',
    ];

    public const FAUTES = ['grave' => 'Faute grave', 'lourde' => 'Faute lourde'];

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'date_cessation', 'motif', 'preavis', 'preavis_mois', 'faute',
        'montant_convenu', 'heures_recherche', 'exception_fin_contrat', 'observations', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'date_cessation' => 'date',
            'preavis_mois' => 'decimal:2',
            'montant_convenu' => 'decimal:2',
            'heures_recherche' => 'decimal:2',
            'exception_fin_contrat' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function estLicenciement(): bool
    {
        return in_array($this->motif, ['licenciement_personnel', 'licenciement_economique'], true);
    }
}
