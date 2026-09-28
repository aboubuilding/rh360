<?php

namespace App\Domain\Paie\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\PeriodePaie;

class PeriodePaiePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('paie.view');
    }

    public function view(Utilisateur $u, PeriodePaie $p): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $p->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('paie.manage');
    }

    public function saisir(Utilisateur $u, PeriodePaie $p): bool
    {
        return $u->peut('paie.manage')
            && $u->entreprise_id === $p->entreprise_id
            && ! $p->estFigee();
    }

    public function calculer(Utilisateur $u, PeriodePaie $p): bool
    {
        return $u->peut('paie.calculer')
            && $u->entreprise_id === $p->entreprise_id
            && $p->peutEtreCalculee();
    }

    public function valider(Utilisateur $u, PeriodePaie $p): bool
    {
        return $u->peut('paie.valider')
            && $u->entreprise_id === $p->entreprise_id
            && ! $p->estFigee();
    }

    public function reouvrir(Utilisateur $u, PeriodePaie $p): bool
    {
        // Seul le super admin peut rouvrir une période validée, exceptionnellement
        return $u->estSuperAdmin() && $p->estFigee();
    }

    public function supprimer(Utilisateur $u, PeriodePaie $p): bool
    {
        return $u->peut('paie.manage')
            && $u->entreprise_id === $p->entreprise_id
            && ! $p->estFigee();
    }
}