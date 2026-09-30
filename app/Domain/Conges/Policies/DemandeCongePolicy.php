<?php

namespace App\Domain\Conges\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;

class DemandeCongePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('conges.view');
    }

    public function view(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.view') && $u->entreprise_id === $d->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('conges.manage');
    }

    public function update(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $d->entreprise_id
            && $d->statut->estModifiable();
    }

    public function delete(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $d->entreprise_id
            && $d->statut === StatutDemandeConge::BROUILLON;
    }

    public function soumettre(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.manage')
            && $u->entreprise_id === $d->entreprise_id
            && $d->statut === StatutDemandeConge::BROUILLON;
    }

    public function autoriser(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && $d->statut === StatutDemandeConge::SOUMISE;
    }

    public function refuser(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && $d->statut === StatutDemandeConge::SOUMISE;
    }

    public function programmer(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && in_array($d->statut, [StatutDemandeConge::AUTORISEE, StatutDemandeConge::PROGRAMMEE], true);
    }

    public function demarrer(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && in_array($d->statut, [StatutDemandeConge::PROGRAMMEE, StatutDemandeConge::AUTORISEE], true)
            // Départ effectif : pas avant la date de départ
            && $d->date_debut !== null
            && ! $d->date_debut->isFuture();
    }

    public function confirmerReprise(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && in_array($d->statut, [StatutDemandeConge::EN_COURS, StatutDemandeConge::PROGRAMMEE], true)
            // À partir de la date de reprise prévue uniquement (CDC §4.1)
            && $d->date_reprise !== null
            && ! $d->date_reprise->isFuture();
    }

    public function annuler(Utilisateur $u, DemandeConge $d): bool
    {
        return $u->peut('conges.validate')
            && $u->entreprise_id === $d->entreprise_id
            && ! $d->statut->estFinal();
    }

    public function importerPlanning(Utilisateur $u): bool
    {
        return $u->peut('conges.manage');
    }
}