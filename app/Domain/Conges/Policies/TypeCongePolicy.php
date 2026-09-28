<?php

namespace App\Domain\Conges\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\TypeConge;

class TypeCongePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('conges.view');
    }

    public function view(Utilisateur $u, TypeConge $t): bool
    {
        return $u->peut('conges.view') && $u->entreprise_id === $t->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('conges.manage');
    }

    public function update(Utilisateur $u, TypeConge $t): bool
    {
        return $u->peut('conges.manage') && $u->entreprise_id === $t->entreprise_id;
    }

    public function delete(Utilisateur $u, TypeConge $t): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $t->entreprise_id
            && ! $t->soldes()->exists()
            && ! $t->demandes()->exists();
    }
}