<?php

namespace App\Domain\Interim\Services;

use App\Domain\Interim\Models\ParametreInterim;
use App\Domain\Shared\Services\ParametresEntreprise;

/**
 * Paramètres de la gestion des intérims d'une entreprise : la valeur saisie dans
 * l'écran de paramétrage, sinon la valeur par défaut de config/interim.php.
 */
class ParametresInterim extends ParametresEntreprise
{
    protected function prefixeConfig(): string
    {
        return 'interim';
    }

    public function definitions(): array
    {
        return CatalogueParametresInterim::definitions();
    }

    protected function modele(): string
    {
        return ParametreInterim::class;
    }
}
