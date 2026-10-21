<?php

namespace App\Domain\Paie\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètre externe de la paie, versionné par date d'effet (SMIG fixé par arrêté…).
 */
class ParametreLegalPaie extends Model
{
    use BelongsToEntreprise;

    protected $table = 'parametres_legaux_paie';

    public const LIBELLES = ['smig' => 'SMIG mensuel (FCFA)'];

    protected $fillable = ['entreprise_id', 'cle', 'valeur', 'debut_effet', 'commentaire'];

    protected function casts(): array
    {
        return ['valeur' => 'decimal:4', 'debut_effet' => 'date'];
    }

    // Valeur en vigueur à une date : la version la plus récente dont l'effet a commencé.
    public static function valeur(int $entrepriseId, string $cle, CarbonInterface $date): ?float
    {
        $valeur = static::pourEntreprise($entrepriseId)
            ->where('cle', $cle)
            ->where('debut_effet', '<=', $date->toDateString())
            ->orderByDesc('debut_effet')
            ->value('valeur');

        return $valeur === null ? null : (float) $valeur;
    }
}
