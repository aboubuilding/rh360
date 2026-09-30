<?php

namespace App\Domain\Classification\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Classification\Models\ReferentielClassification;

class ReferentielPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('classification.view');
    }

    public function view(Utilisateur $u, ReferentielClassification $r): bool
    {
        return $u->peut('classification.view') && $u->entreprise_id === $r->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('classification.manage');
    }

    public function update(Utilisateur $u, ReferentielClassification $r): bool
    {
        return $u->peut('classification.manage') && $u->entreprise_id === $r->entreprise_id;
    }

    public function delete(Utilisateur $u, ReferentielClassification $r): bool
    {
        return $this->update($u, $r);
    }
}