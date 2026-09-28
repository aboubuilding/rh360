<?php

namespace App\Domain\Formation\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Formation\Models\Formation;

class FormationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('formation.view');
    }

    public function view(Utilisateur $u, Formation $f): bool
    {
        return $u->peut('formation.view') && $u->entreprise_id === $f->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('formation.manage');
    }

    public function update(Utilisateur $u, Formation $f): bool
    {
        return $u->peut('formation.manage') && $u->entreprise_id === $f->entreprise_id;
    }

    public function delete(Utilisateur $u, Formation $f): bool
    {
        return $u->peut('formation.manage')
            && $u->entreprise_id === $f->entreprise_id
            && ! $f->sessions()->exists();
    }
}