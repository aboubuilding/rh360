<?php

namespace App\Domain\Performance\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Models\EntretienEvaluation;

class EntretienEvaluationPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('performance.view');
    }

    public function view(Utilisateur $u, EntretienEvaluation $e): bool
    {
        return $u->peut('performance.view') && $u->entreprise_id === $e->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('performance.manage') || $u->peut('performance.evaluer');
    }

    public function update(Utilisateur $u, EntretienEvaluation $e): bool
    {
        return ($u->peut('performance.manage') || $u->peut('performance.evaluer'))
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut !== StatutEntretien::VALIDE;
    }

    public function saisirAutoEvaluation(Utilisateur $u, EntretienEvaluation $e): bool
    {
        return ($u->peut('performance.manage') || $u->peut('performance.evaluer'))
            && $u->entreprise_id === $e->entreprise_id
            && in_array($e->statut, [StatutEntretien::A_PREPARER, StatutEntretien::AUTO_EVALUE], true);
    }

    public function valider(Utilisateur $u, EntretienEvaluation $e): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut === StatutEntretien::REALISE;
    }

    public function delete(Utilisateur $u, EntretienEvaluation $e): bool
    {
        return $u->peut('performance.manage')
            && $u->entreprise_id === $e->entreprise_id
            && $e->statut === StatutEntretien::A_PREPARER;
    }
}