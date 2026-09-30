<?php

/*
| CDC §6 — Circuits de validation, testés sur les règles de transition pures
| (sans base de données).
*/

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Services\CircuitMouvement;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Paie\Enums\StatutPeriode;

describe('contrats : brouillon → soumis → validé → signé / référencé', function () {
    it('autorise le circuit nominal', function (StatutContrat $de, StatutContrat $vers) {
        expect($de->peutTransitionnerVers($vers))->toBeTrue();
    })->with([
        [StatutContrat::BROUILLON, StatutContrat::SOUMIS],
        [StatutContrat::SOUMIS, StatutContrat::VALIDE],
        [StatutContrat::VALIDE, StatutContrat::SIGNE],
    ]);

    it('autorise le retour au brouillon et l\'annulation avant signature', function (StatutContrat $de, StatutContrat $vers) {
        expect($de->peutTransitionnerVers($vers))->toBeTrue();
    })->with([
        [StatutContrat::SOUMIS, StatutContrat::BROUILLON],
        [StatutContrat::VALIDE, StatutContrat::BROUILLON],
        [StatutContrat::BROUILLON, StatutContrat::ANNULE],
        [StatutContrat::SOUMIS, StatutContrat::ANNULE],
        [StatutContrat::VALIDE, StatutContrat::ANNULE],
    ]);

    it('interdit de sauter une étape', function (StatutContrat $de, StatutContrat $vers) {
        expect($de->peutTransitionnerVers($vers))->toBeFalse();
    })->with([
        [StatutContrat::BROUILLON, StatutContrat::VALIDE],
        [StatutContrat::BROUILLON, StatutContrat::SIGNE],
        [StatutContrat::SOUMIS, StatutContrat::SIGNE],
    ]);

    it('fige un contrat signé ou annulé', function (StatutContrat $final) {
        expect($final->estFinal())->toBeTrue();
        foreach (StatutContrat::cases() as $cible) {
            expect($final->peutTransitionnerVers($cible))->toBeFalse();
        }
    })->with([StatutContrat::SIGNE, StatutContrat::ANNULE]);

    it('conserve les valeurs codées de l\'application d\'origine', function () {
        expect(array_column(StatutContrat::cases(), 'value'))
            ->toBe(['draft', 'submitted', 'validated', 'signed', 'cancelled']);
    });

    it('fournit un libellé français pour chaque statut', function () {
        expect(StatutContrat::options())->toHaveCount(5)
            ->and(StatutContrat::SIGNE->libelle())->toBe('Signé / référencé');
    });
});

describe('actes de carrière : proposition → contrôle → validation → programmation → effet', function () {
    beforeEach(fn () => $this->circuit = new CircuitMouvement());

    it('autorise le circuit nominal', function (StatutMouvement $de, StatutMouvement $vers) {
        expect($this->circuit->peutTransitionner($de, $vers))->toBeTrue();
    })->with([
        [StatutMouvement::BROUILLON, StatutMouvement::PROPOSE],
        [StatutMouvement::PROPOSE, StatutMouvement::A_VERIFIER],
        [StatutMouvement::A_VERIFIER, StatutMouvement::VERIFIE],
        [StatutMouvement::VERIFIE, StatutMouvement::VALIDE],
        [StatutMouvement::VALIDE, StatutMouvement::PROGRAMME],
        [StatutMouvement::PROGRAMME, StatutMouvement::EFFECTIF],
        [StatutMouvement::EFFECTIF, StatutMouvement::TERMINE],
    ]);

    it('interdit de valider sans contrôle RH préalable', function () {
        expect($this->circuit->peutTransitionner(StatutMouvement::PROPOSE, StatutMouvement::VALIDE))->toBeFalse();
        expect($this->circuit->peutTransitionner(StatutMouvement::A_VERIFIER, StatutMouvement::VALIDE))->toBeFalse();
    });

    it('interdit de rendre effectif un acte non programmé', function () {
        expect($this->circuit->peutTransitionner(StatutMouvement::VERIFIE, StatutMouvement::EFFECTIF))->toBeFalse();
    });

    it('fige un acte rejeté, annulé ou terminé', function (StatutMouvement $final) {
        foreach (StatutMouvement::cases() as $cible) {
            expect($this->circuit->peutTransitionner($final, $cible))->toBeFalse();
        }
    })->with([StatutMouvement::REJETE, StatutMouvement::ANNULE, StatutMouvement::TERMINE]);

    it('n\'autorise plus l\'annulation d\'un acte effectif', function () {
        expect($this->circuit->peutTransitionner(StatutMouvement::EFFECTIF, StatutMouvement::ANNULE))->toBeFalse();
    });
});

describe('congés : réservation puis consommation des jours', function () {
    it('réserve les jours d\'une demande autorisée, programmée ou en cours', function (StatutDemandeConge $s) {
        expect($s->reserveDesJours())->toBeTrue();
        expect($s->consommeDesJours())->toBeFalse();
    })->with([StatutDemandeConge::AUTORISEE, StatutDemandeConge::PROGRAMMEE, StatutDemandeConge::EN_COURS]);

    it('consomme les jours uniquement à la reprise confirmée', function () {
        expect(StatutDemandeConge::REPRISE_CONFIRMEE->consommeDesJours())->toBeTrue();
        expect(StatutDemandeConge::REPRISE_CONFIRMEE->reserveDesJours())->toBeFalse();
    });

    it('ne réserve rien pour une demande non autorisée', function (StatutDemandeConge $s) {
        expect($s->reserveDesJours())->toBeFalse();
        expect($s->consommeDesJours())->toBeFalse();
    })->with([
        StatutDemandeConge::BROUILLON, StatutDemandeConge::SOUMISE,
        StatutDemandeConge::REFUSEE, StatutDemandeConge::ANNULEE,
    ]);

    it('interdit d\'autoriser une demande non soumise', function () {
        expect(StatutDemandeConge::BROUILLON->peutTransitionnerVers(StatutDemandeConge::AUTORISEE))->toBeFalse();
    });

    it('fige les demandes terminées, refusées ou annulées', function (StatutDemandeConge $final) {
        expect($final->estFinal())->toBeTrue();
        expect($final->transitionsAutorisees())->toBe([]);
    })->with([StatutDemandeConge::REPRISE_CONFIRMEE, StatutDemandeConge::REFUSEE, StatutDemandeConge::ANNULEE]);
});

describe('paie : une période validée est figée', function () {
    it('fige uniquement la période validée', function () {
        expect(StatutPeriode::VALIDEE->estFigee())->toBeTrue();
        expect(StatutPeriode::OUVERTE->estFigee())->toBeFalse();
        expect(StatutPeriode::CALCULEE->estFigee())->toBeFalse();
    });
});
