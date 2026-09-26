<?php

namespace App\Domain\Administration\Policies;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\Utilisateur;

class EntreprisePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('admin.entreprise.view');
    }

    public function view(Utilisateur $u, Entreprise $e): bool
    {
        return $u->peut('admin.entreprise.view') && $u->entreprise_id === $e->id;
    }

    public function update(Utilisateur $u, Entreprise $e): bool
    {
        return $u->peut('admin.entreprise.manage') && $u->entreprise_id === $e->id;
    }
}