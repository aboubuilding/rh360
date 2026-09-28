<?php

namespace App\Domain\Performance\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Performance\Models\ObjectifEvaluation;

class ObjectifEvaluationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('performance.view');
    }

    public function view(Utilisateur $u, ObjectifEvaluation $o): bool
    {
        return $u->peut('performance.view') && $u->entreprise_id === $o->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('performance.manage');
    }

    public function update(Utilisateur $u, ObjectifEvaluation $o): bool
    {
        return $u->peut('performance.manage') && $u->entreprise_id === $o->entreprise_id;
    }

    public function delete(Utilisateur $u, ObjectifEvaluation $o): bool
    {
        return $u->peut('performance.manage') && $u->entreprise_id === $o->entreprise_id;
    }
}