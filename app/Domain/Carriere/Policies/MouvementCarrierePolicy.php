<?php

namespace App\Domain\Carriere\Policies;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;

class MouvementCarrierePolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('carriere.view');
    }

    public function view(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.view') && $u->entreprise_id === $m->entreprise_id;
    }

    public function create(Utilisateur $u): bool
    {
        return $u->peut('carriere.manage');
    }

    public function update(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut->estModifiable();
    }

    public function delete(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut === StatutMouvement::BROUILLON;
    }

    public function soumettre(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $m->entreprise_id
            && in_array($m->statut, [StatutMouvement::BROUILLON, StatutMouvement::PROPOSE], true);
    }

    public function controler(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut === StatutMouvement::PROPOSE;
    }

    public function verifier(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut === StatutMouvement::A_VERIFIER;
    }

    public function valider(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            // Circuit : à vérifier → vérifié (verifier) → validé ; pas de saut d'étape
            && $m->statut === StatutMouvement::VERIFIE;
    }

    public function rejeter(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut->estEnCircuit();
    }

    public function programmer(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut === StatutMouvement::VALIDE;
    }

    public function annuler(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            && ! $m->statut->estFinal();
    }

    public function cloturer(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.validate')
            && $u->entreprise_id === $m->entreprise_id
            && $m->statut === StatutMouvement::EFFECTIF;
    }

    public function gererInterim(Utilisateur $u, MouvementCarriere $m): bool
    {
        return $u->peut('carriere.manage')
            && $u->entreprise_id === $m->entreprise_id
            && $m->type_mouvement->value === 'interim';
    }
}