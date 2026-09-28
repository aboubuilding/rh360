<?php

namespace App\Domain\Formation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\PlanFormation;

class PlanFormationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('formation.view');
    }

    public function view(Utilisateur $u, PlanFormation $p): bool
    {
        return $u->peut('formation.view') && $u->entreprise_id === $p->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('formation.manage');
    }

    public function update(Utilisateur $u, PlanFormation $p): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $p->entreprise_id;
    }

    public function delete(Utilisateur $u, PlanFormation $p): bool
    {
        return $u->peut('formation.manage')
            && $u->entreprise_id === $p->entreprise_id
            && ! $p->sessions()->exists();
    }
}