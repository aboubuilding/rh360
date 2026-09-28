<?php

namespace App\Domain\Sst\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Sst\Models\VisiteMedicale;

class VisiteMedicalePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('health.view');
    }

    public function view(Utilisateur $u, VisiteMedicale $v): bool
    {
        // Accès nominatif strictement réservé à la permission santé
        return $u->peut('health.view')
            && $u->peut('sensitive.social_health')
            && $u->entreprise_id === $v->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('health.manage') && $u->peut('sensitive.social_health');
    }

    public function update(Utilisateur $u, VisiteMedicale $v): bool
    {
        return $u->peut('health.manage')
            && $u->peut('sensitive.social_health')
            && $u->entreprise_id === $v->entreprise_id;
    }

    public function delete(Utilisateur $u, VisiteMedicale $v): bool
    {
        return $this->update($u, $v);
    }

    /**
     * Accès au reporting agrégé anonymisé : accessible aux profils
     * reporting (Direction, Auditeur) SANS permission santé nominative.
     */
    public function reporting(Utilisateur $u): bool
    {
        return $u->peut('health.view') || $u->peut('reports.sst');
    }

    public function exporter(Utilisateur $u): bool
    {
        return $u->peut('sensitive.social_health') && $u->peut('health.view');
    }
}