<?php

namespace App\Domain\Organisation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Organisation\Models\TypeStructure;

class TypeStructurePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('organisation.view');
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('organisation.manage');
    }

    public function update(Utilisateur $u, TypeStructure $t): bool
    {
        return $u->peut('organisation.manage') && $u->entreprise_id === $t->entreprise_id;
    }

    public function delete(Utilisateur $u, TypeStructure $t): bool
    {
        return $this->update($u, $t);
    }
}