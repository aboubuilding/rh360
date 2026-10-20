<?php

namespace App\Domain\Interim\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Interim\Models\InterimInterne;

/**
 * Les intérims internes relèvent des droits du module Carrière.
 */
class InterimInternePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('carriere.view');
    }

    public function view(Utilisateur $u, InterimInterne $interim): bool
    {
        return $u->peut('carriere.view') && $u->entreprise_id === $interim->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('carriere.manage');
    }

    public function gerer(Utilisateur $u, InterimInterne $interim): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $interim->entreprise_id
            && ! $interim->estClos();
    }
}
