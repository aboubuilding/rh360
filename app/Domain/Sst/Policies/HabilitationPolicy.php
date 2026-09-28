<?php

namespace App\Domain\Sst\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Sst\Models\Habilitation;

class HabilitationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('habilitations.view');
    }

    public function view(Utilisateur $u, Habilitation $h): bool
    {
        return $u->peut('habilitations.view') && $u->entreprise_id === $h->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('habilitations.manage');
    }

    public function update(Utilisateur $u, Habilitation $h): bool
    {
        return $u->peut('habilitations.manage') && $u->entreprise_id === $h->entreprise_id;
    }

    public function renouveler(Utilisateur $u, Habilitation $h): bool
    {
        return $u->peut('habilitations.manage') && $u->entreprise_id === $h->entreprise_id;
    }

    public function revoquer(Utilisateur $u, Habilitation $h): bool
    {
        return $u->peut('habilitations.manage') && $u->entreprise_id === $h->entreprise_id;
    }

    public function delete(Utilisateur $u, Habilitation $h): bool
    {
        return $u->peut('habilitations.manage')
            && $u->entreprise_id === $h->entreprise_id
            && $h->statut === \App\Domain\Sst\Enums\StatutHabilitation::BROUILLON;
    }
}