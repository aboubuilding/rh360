<?php

namespace App\Domain\Personnel\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\DocumentSalarie;

class DocumentSalariePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('salaries.view');
    }

    public function view(Utilisateur $u, DocumentSalarie $d): bool
    {
        return $u->peut('salaries.view') && $u->entreprise_id === $d->salarie->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('salaries.manage');
    }

    public function update(Utilisateur $u, DocumentSalarie $d): bool
    {
        return $u->peut('salaries.manage');
    }

    public function delete(Utilisateur $u, DocumentSalarie $d): bool
    {
        return $u->peut('salaries.manage');
    }
}