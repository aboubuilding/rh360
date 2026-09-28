<?php

namespace App\Domain\Carriere\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;

class SituationCarrierePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('carriere.view');
    }

    public function view(Utilisateur $u, SituationCarriere $s): bool
    {
        return $u->peut('carriere.view') && $u->entreprise_id === $s->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('carriere.manage');
    }

    public function update(Utilisateur $u, SituationCarriere $s): bool
    {
        return $u->peut('carriere.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function reprendre(Utilisateur $u): bool
    {
        return $u->peut('carriere.manage');
    }

    public function confirmerFiabilite(Utilisateur $u, SituationCarriere $s): bool
    {
        return $u->peut('carriere.validate') && $u->entreprise_id === $s->entreprise_id;
    }
}