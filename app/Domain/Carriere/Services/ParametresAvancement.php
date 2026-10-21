<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Carriere\Models\ParametreAvancement;
use App\Domain\Shared\Services\ParametresEntreprise;

/**
 * Paramètres des avancements et de l'ancienneté d'une entreprise : la valeur saisie dans
 * l'écran de paramétrage, sinon la valeur par défaut de config/avancement.php.
 */
class ParametresAvancement extends ParametresEntreprise
{
    protected function prefixeConfig(): string
    {
        return 'avancement';
    }

    public function definitions(): array
    {
        return CatalogueParametresAvancement::definitions();
    }

    protected function modele(): string
    {
        return ParametreAvancement::class;
    }
}
