<?php

namespace App\Domain\Administration\Policies;

use App\Domain\Administration\Models\Utilisateur;

class UtilisateurPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('admin.utilisateurs.view');
    }

    public function view(Utilisateur $u, Utilisateur $cible): bool
    {
        return $u->peut('admin.utilisateurs.view') && $u->entreprise_id === $cible->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('admin.utilisateurs.manage');
    }

    public function update(Utilisateur $u, Utilisateur $cible): bool
    {
        return $u->peut('admin.utilisateurs.manage')
            && $u->entreprise_id === $cible->entreprise_id
            && ! ($cible->estSuperAdmin() && ! $u->estSuperAdmin());
    }

    public function delete(Utilisateur $u, Utilisateur $cible): bool
    {
        return $this->update($u, $cible)
            && $u->id !== $cible->id
            && ! $cible->estSuperAdmin();
    }

    public function gererPermissions(Utilisateur $u): bool
    {
        return $u->peut('admin.permissions.manage');
    }
}