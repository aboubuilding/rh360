<?php

namespace App\Domain\Formation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\BesoinFormation;

class BesoinFormationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('formation.view');
    }

    public function view(Utilisateur $u, BesoinFormation $b): bool
    {
        return $u->peut('formation.view') && $u->entreprise_id === $b->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('formation.manage');
    }

    public function update(Utilisateur $u, BesoinFormation $b): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $b->entreprise_id;
    }

    public function valider(Utilisateur $u, BesoinFormation $b): bool
    {
        return $u->peut('formation.validate') && $u->entreprise_id === $b->entreprise_id;
    }

    public function delete(Utilisateur $u, BesoinFormation $b): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $b->entreprise_id;
    }
}