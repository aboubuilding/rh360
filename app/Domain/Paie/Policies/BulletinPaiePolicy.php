<?php

namespace App\Domain\Paie\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Paie\Models\BulletinPaie;

class BulletinPaiePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('paie.view');
    }

    public function view(Utilisateur $u, BulletinPaie $b): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $b->entreprise_id;
    }

    public function imprimer(Utilisateur $u, BulletinPaie $b): bool
    {
        return $u->peut('paie.view') && $u->entreprise_id === $b->entreprise_id;
    }

    public function exporter(Utilisateur $u): bool
    {
        return $u->peut('paie.export');
    }
}