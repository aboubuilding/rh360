<?php

/*
| Règles métier pures (sans base de données) issues du CDC §4.
*/

use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;
use App\Domain\Sst\Enums\NiveauRisque;
use App\Domain\Sst\Services\CalculateurScoreRisque;

describe('SST — évaluation des risques (CDC §4 7.3)', function () {
    beforeEach(fn () => $this->calculateur = new CalculateurScoreRisque());

    it('calcule le score gravité × probabilité et son niveau', function (int $g, int $p, int $score, NiveauRisque $niveau) {
        expect($this->calculateur->calculer($g, $p))->toBe(['score' => $score, 'niveau' => $niveau]);
    })->with([
        'minimum' => [1, 1, 1, NiveauRisque::FAIBLE],
        'limite faible' => [2, 2, 4, NiveauRisque::FAIBLE],
        'début moyen' => [1, 5, 5, NiveauRisque::MOYEN],
        'limite moyen' => [3, 3, 9, NiveauRisque::MOYEN],
        'début élevé' => [2, 5, 10, NiveauRisque::ELEVE],
        'limite élevé' => [4, 4, 16, NiveauRisque::ELEVE],
        'critique' => [4, 5, 20, NiveauRisque::CRITIQUE],
        'maximum' => [5, 5, 25, NiveauRisque::CRITIQUE],
    ]);

    it('rejette une cotation hors de l\'échelle 1 à 5', function (int $g, int $p) {
        $this->calculateur->calculer($g, $p);
    })->throws(InvalidArgumentException::class)->with([[0, 3], [6, 3], [3, 0], [3, 6]]);

    it('raccourcit le délai de revue quand le niveau augmente', function () {
        $revues = collect([NiveauRisque::FAIBLE, NiveauRisque::MOYEN, NiveauRisque::ELEVE, NiveauRisque::CRITIQUE])
            ->map(fn ($n) => $this->calculateur->suggererProchaineRevue($n)->timestamp);

        expect($revues->all())->toBe($revues->sortDesc()->values()->all());
    });
});

describe('Recrutement — suivi des étapes du candidat (CDC §4 6.3)', function () {
    beforeEach(fn () => $this->suivi = new SuiviEtapesCandidat());

    it('fait progresser le candidat dans le pipeline', function (EtapeCandidat $de, EtapeCandidat $vers) {
        expect($this->suivi->peutTransitionner($de, $vers))->toBeTrue();
    })->with([
        [EtapeCandidat::CANDIDATURE_RECUE, EtapeCandidat::PRESELECTION],
        [EtapeCandidat::PRESELECTION, EtapeCandidat::ENTRETIEN_1],
        [EtapeCandidat::ENTRETIEN_1, EtapeCandidat::ENTRETIEN_2],
        [EtapeCandidat::ENTRETIEN_2, EtapeCandidat::OFFRE],
        [EtapeCandidat::OFFRE, EtapeCandidat::ACCEPTE],
    ]);

    it('interdit l\'intégration sans offre', function (EtapeCandidat $de) {
        expect($this->suivi->peutTransitionner($de, EtapeCandidat::ACCEPTE))->toBeFalse();
    })->with([EtapeCandidat::CANDIDATURE_RECUE, EtapeCandidat::PRESELECTION, EtapeCandidat::ENTRETIEN_2]);

    it('permet le refus ou le retrait à toute étape en cours', function (EtapeCandidat $de) {
        expect($this->suivi->peutTransitionner($de, EtapeCandidat::REFUSE))->toBeTrue();
        expect($this->suivi->peutTransitionner($de, EtapeCandidat::RETIRE))->toBeTrue();
    })->with([EtapeCandidat::CANDIDATURE_RECUE, EtapeCandidat::ENTRETIEN_1, EtapeCandidat::OFFRE]);

    it('fige un candidat accepté, refusé ou retiré', function (EtapeCandidat $final) {
        foreach (EtapeCandidat::cases() as $cible) {
            expect($this->suivi->peutTransitionner($final, $cible))->toBeFalse();
        }
    })->with([EtapeCandidat::ACCEPTE, EtapeCandidat::REFUSE, EtapeCandidat::RETIRE]);
});

describe('Carrière — nature des actes (CDC §4 3.2 et 3.4)', function () {
    it('classe l\'intérim comme temporaire, sans effet sur le poste permanent', function () {
        expect(TypeMouvement::INTERIM->estTemporaire())->toBeTrue();
        expect(TypeMouvement::INTERIM->estAffectation())->toBeFalse();
    });

    it('distingue les actes d\'affectation et de carrière', function () {
        expect(TypeMouvement::MUTATION->estAffectation())->toBeTrue();
        expect(TypeMouvement::PROMOTION->estCarriere())->toBeTrue();
        expect(TypeMouvement::AVANCEMENT_ANTICIPE->estCarriere())->toBeTrue();
        expect(TypeMouvement::RECLASSEMENT->estCarriere())->toBeTrue();
        expect(TypeMouvement::AFFECTATION->estCarriere())->toBeFalse();
    });

    it('couvre les six types d\'actes du cahier des charges', function () {
        $valeurs = array_column(TypeMouvement::cases(), 'value');
        expect($valeurs)->toContain('affectation', 'mutation', 'promotion', 'interim', 'avancement_anticipe', 'reclassement');
    });
});
