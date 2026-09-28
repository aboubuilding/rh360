<?php

namespace App\Domain\Recrutement\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;

class BesoinRecrutementPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('recrutement.view');
    }

    public function view(Utilisateur $u, BesoinRecrutement $b): bool
    {
        return $u->peut('recrutement.view') && $u->entreprise_id === $b->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('recrutement.manage');
    }

    public function update(Utilisateur $u, BesoinRecrutement $b): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $b->entreprise_id
            && $b->statut !== StatutBesoinRecrutement::POURVU;
    }

    public function valider(Utilisateur $u, BesoinRecrutement $b): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $b->entreprise_id
            && $b->statut === StatutBesoinRecrutement::A_VALIDER;
    }

    public function delete(Utilisateur $u, BesoinRecrutement $b): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $b->entreprise_id
            && $b->statut === StatutBesoinRecrutement::A_VALIDER;
    }
}