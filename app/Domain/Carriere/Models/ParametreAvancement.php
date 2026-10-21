<?php

namespace App\Domain\Carriere\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;

/**
 * Surcharges, pour une entreprise, des paramètres par défaut de config/avancement.php.
 */
class ParametreAvancement extends Model
{
    use BelongsToEntreprise;

    protected $table = 'parametres_avancement';

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
