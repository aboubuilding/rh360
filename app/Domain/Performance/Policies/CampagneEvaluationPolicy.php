<?php

namespace App\Domain\Performance\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Performance\Models\CampagneEvaluation;

class CampagneEvaluationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('performance.view');
    }

    public function view(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.view') && $u->entreprise_id === $c->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('performance.manage');
    }

    public function update(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut !== StatutCampagne::ARCHIVEE;
    }

    public function cloturer(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutCampagne::EN_COURS;
    }

    public function archiver(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutCampagne::CLOTUREE;
    }

    public function delete(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutCampagne::PREPARATION;
    }

    public function gererCriteres(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage') && $u->entreprise_id === $c->entreprise_id;
    }

    public function gererObjectifs(Utilisateur $u, CampagneEvaluation $c): bool
    {
        return $u->peut('performance.manage') && $u->entreprise_id === $c->entreprise_id;
    }
}