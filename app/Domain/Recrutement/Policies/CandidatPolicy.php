<?php

namespace App\Domain\Recrutement\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Recrutement\Models\Candidat;

class CandidatPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('recrutement.view');
    }

    public function view(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.view') && $u->entreprise_id === $c->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('recrutement.manage');
    }

    public function update(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.manage') && $u->entreprise_id === $c->entreprise_id;
    }

    public function changerEtape(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->estEnCours();
    }

    public function decider(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->estEnCours();
    }

    public function integrer(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->decision?->value === 'retenu';
    }

    public function delete(Utilisateur $u, Candidat $c): bool
    {
        return $u->peut('recrutement.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->etape?->value === 'candidature_recue';
    }
}