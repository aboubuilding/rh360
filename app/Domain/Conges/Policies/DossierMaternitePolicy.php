<?php

namespace App\Domain\Conges\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\DossierMaternite;

class DossierMaternitePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        // Registre confidentiel : accessible uniquement aux permissions
        // sensibles social/santé
        return $u->peut('sensitive.social_health');
    }

    public function view(Utilisateur $u, DossierMaternite $d): bool
    {
        return $u->peut('sensitive.social_health') && $u->entreprise_id === $d->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('sensitive.social_health') && $u->peut('conges.manage');
    }

    public function update(Utilisateur $u, DossierMaternite $d): bool
    {
        return $u->peut('sensitive.social_health')
            && $u->peut('conges.manage')
            && $u->entreprise_id === $d->entreprise_id;
    }

    public function exporter(Utilisateur $u): bool
    {
        return $u->peut('sensitive.social_health');
    }
}