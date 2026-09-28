<?php

namespace App\Domain\Paie\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\RubriquePaie;

class RubriquePaiePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('paie.view');
    }

    public function view(Utilisateur $u, RubriquePaie $r): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $r->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('paie.manage');
    }

    public function update(Utilisateur $u, RubriquePaie $r): bool
    {
        return $u->peut('paie.manage') && $u->entreprise_id === $r->entreprise_id;
    }

    public function delete(Utilisateur $u, RubriquePaie $r): bool
    {
        return $u->peut('paie.manage')
            && $u->entreprise_id === $r->entreprise_id
            && ! $r->lignesBulletins()->exists();
    }
}