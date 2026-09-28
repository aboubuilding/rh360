<?php

namespace App\Domain\Formation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\ParticipantFormation;

class ParticipantFormationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('formation.view');
    }

    public function view(Utilisateur $u, ParticipantFormation $p): bool
    {
        return $u->peut('formation.view') && $u->entreprise_id === $p->entreprise_id;
    }

    public function update(Utilisateur $u, ParticipantFormation $p): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $p->entreprise_id;
    }

    public function evaluer(Utilisateur $u, ParticipantFormation $p): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $p->entreprise_id;
    }

    public function delete(Utilisateur $u, ParticipantFormation $p): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $p->entreprise_id;
    }
}