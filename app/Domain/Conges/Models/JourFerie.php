<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;

/**
 * Jour férié du calendrier de l'entreprise.
 */
class JourFerie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'jours_feries';

    // « propose » : généré par l'ERP, à vérifier ; « saisi » : ajouté par les RH.
    public const ORIGINES = ['propose' => 'Proposé', 'saisi' => 'Saisi'];

    protected $fillable = ['entreprise_id', 'date', 'libelle', 'origine', 'cree_par'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
