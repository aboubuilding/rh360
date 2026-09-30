<?php

namespace App\Domain\Paie\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\ModelePaie;

class ModelePaiePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('paie.view');
    }

    public function view(Utilisateur $u, ModelePaie $m): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $m->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('paie.manage');
    }

    public function update(Utilisateur $u, ModelePaie $m): bool
    {
        return $u->peut('paie.manage') && $u->entreprise_id === $m->entreprise_id;
    }

    public function delete(Utilisateur $u, ModelePaie $m): bool
    {
        return $this->update($u, $m);
    }
}
