<?php

namespace App\Domain\Classification\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\PositionClassification;

class PositionPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('classification.view');
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('classification.manage');
    }

    public function update(Utilisateur $u, PositionClassification $p): bool
    {
        return $u->peut('classification.manage')
            && $u->entreprise_id === $p->referentiel->entreprise_id;
    }

    public function delete(Utilisateur $u, PositionClassification $p): bool
    {
        return $this->update($u, $p);
    }
}