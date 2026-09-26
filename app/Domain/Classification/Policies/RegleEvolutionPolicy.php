<?php

namespace App\Domain\Classification\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\RegleEvolution;

class RegleEvolutionPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('classification.view');
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('classification.manage');
    }

    public function update(Utilisateur $u, RegleEvolution $r): bool
    {
        return $u->peut('classification.manage')
            && $u->entreprise_id === $r->referentiel->entreprise_id;
    }

    public function delete(Utilisateur $u, RegleEvolution $r): bool
    {
        return $this->update($u, $r);
    }
}