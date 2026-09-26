<?php

namespace App\Domain\Personnel\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Personnel\Models\Salarie;

class SalariePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('salaries.view');
    }

    public function view(Utilisateur $u, Salarie $s): bool
    {
        return $u->peut('salaries.view') && $u->entreprise_id === $s->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('salaries.manage');
    }

    public function update(Utilisateur $u, Salarie $s): bool
    {
        return $u->peut('salaries.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function delete(Utilisateur $u, Salarie $s): bool
    {
        return $u->peut('salaries.manage') && $u->entreprise_id === $s->entreprise_id;
    }

    public function fusionner(Utilisateur $u): bool
    {
        return $u->peut('salaries.fusion');
    }

    public function importer(Utilisateur $u): bool
    {
        return $u->peut('salaries.import');
    }

    public function voirDonneesSociales(Utilisateur $u): bool
    {
        return $u->peut('sensitive.social_health');
    }

    public function voirDonneesBancaires(Utilisateur $u): bool
    {
        return $u->peut('sensitive.banking');
    }

    public function voirGps(Utilisateur $u): bool
    {
        return $u->peut('sensitive.gps');
    }
}