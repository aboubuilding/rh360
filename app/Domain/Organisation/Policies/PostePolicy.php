<?php

namespace App\Domain\Organisation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Organisation\Models\Poste;

class PostePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('organisation.view');
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('organisation.manage');
    }

    public function update(Utilisateur $u, Poste $p): bool
    {
        return $u->peut('organisation.manage') && $u->entreprise_id === $p->entreprise_id;
    }

    public function delete(Utilisateur $u, Poste $p): bool
    {
        return $this->update($u, $p);
    }
}