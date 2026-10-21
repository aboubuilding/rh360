<?php

/*
| Paie — absences du mois : retenue des absences non rémunérées (Code art. 160), congé payé
| remplacé par l'allocation (Code art. 202 ; CCIT art. 49 c), maintien maladie selon le barème
| le plus favorable (CCIT art. 16 ; Code art. 69), maternité avec avance de la part CNSS
| (Code art. 190 ; CCIT art. 49 d). Salaire de 90 000 FCFA : 3 000 FCFA par jour (1/30).
*/

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\ParametreDemande;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\LigneBulletin;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\MoteurPaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
    $this->actingAs(Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first());

    $this->salarie = Salarie::where('actif', true)->orderBy('id')->firstOrFail();
    Salarie::where('actif', true)->where('id', '!=', $this->salarie->id)->update(['actif' => false]);
    RegleAnciennete::query()->update(['actif' => false]);
    DB::table('demandes_conges')->where('salarie_id', $this->salarie->id)->delete();
    DB::table('absences')->where('salarie_id', $this->salarie->id)->delete();
    SituationCarriere::where('salarie_id', $this->salarie->id)->first()->positionClassification->update(['salaire_minimum' => 60000]);
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->delete();
    ElementPaieSalarie::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'rubrique_id' => RubriquePaie::where('code', 'SAL-BASE')->value('id'), 'montant' => 90000, 'actif' => true, 'etat' => 1]);
    $this->salarie->update(['date_embauche' => '2023-06-01']);

    $this->periode = fn (int $mois) => PeriodePaie::updateOrCreate(['entreprise_id' => 1, 'annee' => 2026, 'mois' => $mois], ['statut' => 'open', 'etat' => 1]);
    $this->type = fn (string $code) => TypeConge::where('code', $code)->sole();
    $this->demande = fn (string $code, string $debut, string $reprise) => DemandeConge::factory()->create([
        'entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)($code)->id,
        'statut' => StatutDemandeConge::AUTORISEE->value, 'date_debut' => $debut, 'date_reprise' => $reprise,
        'duree_jours' => Carbon::parse($debut)->diffInDays(Carbon::parse($reprise)),
    ]);
    // Calcule le mois et renvoie les montants par code de rubrique.
    $this->bulletin = function (int $mois): array {
        $periode = ($this->periode)($mois);
        app(MoteurPaie::class)->calculer($periode);
        $bulletin = BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->sole();

        return ['brut' => (float) $bulletin->montant_brut] + $bulletin->lignes->groupBy('code')->map(fn ($l) => (float) $l->sum('montant'))->all();
    };
});

it('retient le temps non travaillé d\'une absence non justifiée et d\'un congé sans solde', function () {
    Absence::create(['entreprise_id' => 1, 'salarie_id' => $this->salarie->id, 'type_conge_id' => ($this->type)('ABS-NJ')->id,
        'debut_le' => '2026-09-08 00:00:00', 'fin_le' => '2026-09-09 23:59:59', 'statut' => 'constatée',
        'qualification' => QualificationAbsence::INJUSTIFIEE->value, 'statut_transmission_paie' => 'a_preparer', 'etat' => 1]);
    ($this->demande)('SANS-SOLDE', '2026-09-21', '2026-09-26');

    $b = ($this->bulletin)(9);

    // 2 jours + 5 jours à 3 000.
    expect($b['ABS-RET'])->toBe(-21000.0);
    expect($b['brut'])->toBe(69000.0);
});

it('remplace le salaire des jours de congé par l\'allocation, au moins égale au maintien', function () {
    ($this->demande)('CA', '2026-09-14', '2026-09-24');

    // Sans bulletin antérieur : maintien du salaire.
    $b = ($this->bulletin)(9);
    expect([$b['CONGE-RET'], $b['CONGE-ALLOC'], $b['brut']])->toBe([-30000.0, 30000.0, 90000.0]);
});

it('verse l\'allocation au douzième quand elle dépasse le maintien du salaire', function () {
    // Rémunération des 12 mois précédents : 120 000 par mois, soit 4 000 par jour de congé.
    for ($m = Carbon::parse('2025-09-01'); $m->lte(Carbon::parse('2026-08-01')); $m->addMonth()) {
        $periode = PeriodePaie::firstOrCreate(['entreprise_id' => 1, 'annee' => $m->year, 'mois' => $m->month], ['statut' => 'validated', 'etat' => 1]);
        BulletinPaie::where('periode_id', $periode->id)->where('salarie_id', $this->salarie->id)->delete();
        $bulletin = BulletinPaie::factory()->create(['entreprise_id' => 1, 'periode_id' => $periode->id, 'salarie_id' => $this->salarie->id]);
        LigneBulletin::create(['entreprise_id' => 1, 'bulletin_id' => $bulletin->id, 'code' => 'SAL-BASE', 'libelle' => 'Salaire', 'nature' => 'gain', 'montant' => 120000]);
    }
    ($this->demande)('CA', '2026-09-14', '2026-09-24');

    $b = ($this->bulletin)(9);
    expect([$b['CONGE-RET'], $b['CONGE-ALLOC'], $b['brut']])->toBe([-30000.0, 40000.0, 100000.0]);

    // Si l'entreprise renonce à la comparaison, une allocation inférieure reste au douzième.
    ElementPaieSalarie::where('salarie_id', $this->salarie->id)->update(['montant' => 150000]);
    ParametreDemande::create(['entreprise_id' => 1, 'valeurs' => ['conge_comparaison_maintien' => false]]);
    app()->forgetScopedInstances();
    $b = ($this->bulletin)(9);
    expect([$b['CONGE-RET'], $b['CONGE-ALLOC']])->toBe([-50000.0, 40000.0]);
});

it('maintient le salaire d\'un arrêt maladie à plein puis à demi-salaire', function () {
    // 3 ans d'ancienneté : CCIT 30 jours pleins + 90 jours à demi (plus favorable que le Code).
    ($this->demande)('MAL', '2026-09-01', '2026-11-30');

    $septembre = ($this->bulletin)(9);
    expect([$septembre['MAL-RET'], $septembre['MAL-PLEIN'], $septembre['MAL-DEMI'] ?? 0.0])->toBe([-90000.0, 90000.0, 0.0]);

    $octobre = ($this->bulletin)(10);
    expect([$octobre['MAL-RET'], $octobre['MAL-PLEIN'] ?? 0.0, $octobre['MAL-DEMI']])->toBe([-90000.0, 0.0, 45000.0]);
    expect($octobre['brut'])->toBe(45000.0);
});

it('indemnise la maternité : part employeur et avance de la part CNSS', function () {
    ($this->demande)('MAT', '2026-09-17', '2026-10-01');

    $b = ($this->bulletin)(9);

    // 14 jours à 3 000 : neutralisés, puis 50 % employeur et 50 % avancés pour la CNSS.
    expect([$b['MAT-RET'], $b['MAT-EMP'], $b['MAT-AVANCE']])->toBe([-42000.0, 21000.0, 21000.0]);
    // L'avance n'est ni soumise à cotisation ni imposable.
    $bulletin = BulletinPaie::where('periode_id', ($this->periode)(9)->id)->where('salarie_id', $this->salarie->id)->sole();
    expect((float) $bulletin->assiette_cotisations)->toBe(69000.0);
});

it('ne touche pas au salaire d\'une permission exceptionnelle', function () {
    ($this->demande)('PERM-EVT', '2026-09-10', '2026-09-12');

    $b = ($this->bulletin)(9);

    expect(collect($b)->keys()->intersect(['ABS-RET', 'CONGE-RET', 'MAL-RET'])->isEmpty())->toBeTrue();
    expect($b['brut'])->toBe(90000.0);
});
