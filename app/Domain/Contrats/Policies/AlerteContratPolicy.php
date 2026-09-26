<?php

namespace App\Domain\Contrats\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Models\AlerteContrat;

class AlerteContratPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('contrats.view');
    }

    public function view(Utilisateur $u, AlerteContrat $a): bool
    {
        return $u->peut('contrats.view') && $u->entreprise_id === $a->entreprise_id;
    }

    public function cloturer(Utilisateur $u, AlerteContrat $a): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $a->entreprise_id
            && $a->en_cours;
    }
}