<?php

namespace App\Domain\Contrats\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Models\EvenementEssai;

class EvenementEssaiPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('contrats.view');
    }

    public function view(Utilisateur $u, EvenementEssai $e): bool
    {
        return $u->peut('contrats.view') && $u->entreprise_id === $e->entreprise_id;
    }

    public function declarer(Utilisateur $u): bool
    {
        return $u->peut('contrats.manage');
    }

    public function decider(Utilisateur $u, EvenementEssai $e): bool
    {
        return $u->peut('contrats.validate')
            && $u->entreprise_id === $e->entreprise_id
            && $e->estEnAttente();
    }
}