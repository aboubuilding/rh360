<?php

namespace App\Domain\Carriere\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\InstantaneCarriere;

class InstantaneCarrierePolicy
{
    public function view(Utilisateur $u, InstantaneCarriere $i): bool
    {
        return $u->peut('carriere.view') && $u->entreprise_id === $i->entreprise_id;
    }
}