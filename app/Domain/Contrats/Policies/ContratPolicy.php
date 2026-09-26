<?php

namespace App\Domain\Contrats\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\Contrat;

class ContratPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('contrats.view');
    }

    public function view(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.view') && $u->entreprise_id === $c->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('contrats.manage');
    }

    public function update(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->estModifiable();
    }

    public function delete(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutContrat::BROUILLON;
    }

    public function soumettre(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutContrat::BROUILLON;
    }

    public function valider(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.validate')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutContrat::SOUMIS;
    }

    public function signer(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.sign')
            && $u->entreprise_id === $c->entreprise_id
            && $c->statut === StatutContrat::VALIDE;
    }

    public function retournerBrouillon(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.validate')
            && $u->entreprise_id === $c->entreprise_id
            && in_array($c->statut, [StatutContrat::SOUMIS, StatutContrat::VALIDE], true);
    }

    public function annuler(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.validate')
            && $u->entreprise_id === $c->entreprise_id
            && ! in_array($c->statut, [StatutContrat::SIGNE, StatutContrat::ANNULE], true);
    }

    public function creerAvenant(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $c->entreprise_id
            && $c->estSigne();
    }

    public function gererPieces(Utilisateur $u, Contrat $c): bool
    {
        return $u->peut('contrats.manage')
            && $u->entreprise_id === $c->entreprise_id;
    }
}