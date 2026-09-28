<?php

namespace App\Domain\Formation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\SessionFormation;

class SessionFormationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('formation.view');
    }

    public function view(Utilisateur $u, SessionFormation $s): bool
    {
        return $u->peut('formation.view') && $u->entreprise_id === $s->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('formation.manage');
    }

    public function update(Utilisateur $u, SessionFormation $s): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function gererParticipants(Utilisateur $u, SessionFormation $s): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function evaluer(Utilisateur $u, SessionFormation $s): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function delete(Utilisateur $u, SessionFormation $s): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $s->entreprise_id;
    }
}