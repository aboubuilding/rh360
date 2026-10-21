<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\ParametreDemande;
use App\Domain\Shared\Services\ParametresEntreprise;

/**
 * Paramètres des demandes et autorisations d'une entreprise : la valeur saisie dans l'écran
 * de paramétrage, sinon la valeur par défaut de config/demandes.php.
 */
class ParametresDemandes extends ParametresEntreprise
{
    protected function prefixeConfig(): string
    {
        return 'demandes';
    }

    public function definitions(): array
    {
        return CatalogueParametresDemandes::definitions();
    }

    protected function modele(): string
    {
        return ParametreDemande::class;
    }
}
