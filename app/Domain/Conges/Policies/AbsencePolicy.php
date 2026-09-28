<?php

namespace App\Domain\Conges\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Models\Absence;

class AbsencePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('conges.view');
    }

    public function view(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.view') && $u->entreprise_id === $a->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('conges.manage');
    }

    public function update(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.manage') && $u->entreprise_id === $a->entreprise_id;
    }

    public function qualifier(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.manage') && $u->entreprise_id === $a->entreprise_id;
    }

    public function regulariser(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.validate') && $u->entreprise_id === $a->entreprise_id;
    }

    public function transmettrePaie(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $a->entreprise_id
            && ! $a->estTransmise();
    }

    public function delete(Utilisateur $u, Absence $a): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $a->entreprise_id
            && ! $a->estTransmise();
    }
}