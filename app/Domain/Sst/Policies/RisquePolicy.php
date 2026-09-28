<?php

namespace App\Domain\Sst\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Sst\Models\Risque;

class RisquePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('risks.view');
    }

    public function view(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.view') && $u->entreprise_id === $r->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('risks.manage');
    }

    public function update(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.manage') && $u->entreprise_id === $r->entreprise_id;
    }

    public function archiver(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.manage')
            && $u->entreprise_id === $r->entreprise_id
            && $r->statut === 'active';
    }

    public function evaluer(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.manage') && $u->entreprise_id === $r->entreprise_id;
    }

    public function gererActions(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.manage') && $u->entreprise_id === $r->entreprise_id;
    }

    public function delete(Utilisateur $u, Risque $r): bool
    {
        return $u->peut('risks.manage')
            && $u->entreprise_id === $r->entreprise_id
            && ! $r->evaluations()->exists();
    }
}