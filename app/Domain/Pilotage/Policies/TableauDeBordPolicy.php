<?php

namespace App\Domain\Pilotage\Policies;

use App\Domain\Administration\Models\Utilisateur;

class TableauDeBordPolicy
{
    /**
     * Tout utilisateur connecté et actif peut accéder au tableau de bord.
     * Le contenu est ensuite filtré par les permissions dans le service agrégateur.
     */
    public function view(Utilisateur $u): bool
    {
        return $u->estActif() && $u->peut('dashboard.view');
    }

    /**
     * La recherche rapide est accessible à tout utilisateur avec accès au personnel.
     */
    public function rechercher(Utilisateur $u): bool
    {
        return $u->peut('salaries.view');
    }
}