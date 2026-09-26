<?php

namespace App\Domain\Organisation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Organisation\Models\Structure;

class StructurePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('organisation.view');
    }

    public function view(Utilisateur $u, Structure $s): bool
    {
        return $u->peut('organisation.view') && $u->entreprise_id === $s->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('organisation.manage');
    }

    public function update(Utilisateur $u, Structure $s): bool
    {
        return $u->peut('organisation.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function delete(Utilisateur $u, Structure $s): bool
    {
        return $u->peut('organisation.manage') && $u->entreprise_id === $s->entreprise_id;
    }
}