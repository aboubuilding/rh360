<?php

namespace App\Domain\Sst\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Sst\Models\DotationEpi;

class DotationEpiPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('ppe.view');
    }

    public function view(Utilisateur $u, DotationEpi $d): bool
    {
        return $u->peut('ppe.view') && $u->entreprise_id === $d->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('ppe.manage');
    }

    public function update(Utilisateur $u, DotationEpi $d): bool
    {
        return $u->peut('ppe.manage') && $u->entreprise_id === $d->entreprise_id;
    }

    public function annulerOperation(Utilisateur $u, DotationEpi $d): bool
    {
        return $u->peut('ppe.manage') && $u->entreprise_id === $d->entreprise_id;
    }

    public function delete(Utilisateur $u, DotationEpi $d): bool
    {
        return $u->peut('ppe.manage')
            && $u->entreprise_id === $d->entreprise_id
            && ! $d->operations()->exists();
    }
}