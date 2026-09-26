<?php

namespace App\Domain\Personnel\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\MembreFoyer;

class MembreFoyerPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('salaries.view');
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('salaries.manage');
    }

    public function update(Utilisateur $u, MembreFoyer $m): bool
    {
        return $u->peut('salaries.manage');
    }

    public function delete(Utilisateur $u, MembreFoyer $m): bool
    {
        return $u->peut('salaries.manage');
    }
}