<?php

namespace App\Domain\Conges\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\SoldeConge;

class SoldeCongePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('conges.soldes.view');
    }

    public function view(Utilisateur $u, SoldeConge $s): bool
    {
        return $u->peut('conges.soldes.view') && $u->entreprise_id === $s->entreprise_id;
    }

    public function ajuster(Utilisateur $u, SoldeConge $s): bool
    {
        return $u->peut('conges.soldes.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function creer(Utilisateur $u): bool
    {
        return $u->peut('conges.soldes.manage');
    }
}