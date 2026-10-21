<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;

/**
 * Surcharges, pour une entreprise, des paramètres par défaut de config/demandes.php.
 */
class ParametreDemande extends Model
{
    use BelongsToEntreprise;

    protected $table = 'parametres_demandes';

    protected $primaryKey = 'entreprise_id';

    public $incrementing = false;

    protected $fillable = ['entreprise_id', 'valeurs', 'revision', 'modifie_par'];

    protected function casts(): array
    {
        return [
            'valeurs' => 'array',
            'revision' => 'integer',
        ];
    }
}
