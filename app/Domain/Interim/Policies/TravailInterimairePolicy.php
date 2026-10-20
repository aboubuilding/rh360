<?php

namespace App\Domain\Interim\Policies;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Eloquent\Model;

/**
 * Agences, missions et factures d'intérim : droits du module Carrière, cloisonnés par entreprise.
 */
class TravailInterimairePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('carriere.view');
    }

    public function view(Utilisateur $u, Model $objet): bool
    {
        return $u->peut('carriere.view') && $u->entreprise_id === $objet->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('carriere.manage');
    }

    public function gerer(Utilisateur $u, Model $objet): bool
    {
        return $u->peut('carriere.manage') && $u->entreprise_id === $objet->entreprise_id;
    }
}
