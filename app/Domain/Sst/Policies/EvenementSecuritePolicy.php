<?php

namespace App\Domain\Sst\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Models\EvenementSecurite;

class EvenementSecuritePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('safety.view');
    }

    public function view(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.view') && $u->entreprise_id === $e->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('safety.manage');
    }

    public function update(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut !== StatutEvenementSecurite::CLOTURE;
    }

    public function cloturer(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->estCloturable();
    }

    public function annuler(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut !== StatutEvenementSecurite::CLOTURE;
    }

    public function gererActions(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.manage') && $u->entreprise_id === $e->entreprise_id;
    }

    public function delete(Utilisateur $u, EvenementSecurite $e): bool
    {
        return $u->peut('safety.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut === StatutEvenementSecurite::DECLARE;
    }
}