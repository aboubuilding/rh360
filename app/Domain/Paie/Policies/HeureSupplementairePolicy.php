<?php

namespace App\Domain\Paie\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\HeureSupplementaire;

class HeureSupplementairePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('paie.view');
    }

    public function view(Utilisateur $u, HeureSupplementaire $h): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $h->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('paie.manage');
    }

    public function update(Utilisateur $u, HeureSupplementaire $h): bool
    {
        return $u->peut('paie.manage') && $u->entreprise_id === $h->entreprise_id;
    }

    public function delete(Utilisateur $u, HeureSupplementaire $h): bool
    {
        return $this->update($u, $h);
    }
}
