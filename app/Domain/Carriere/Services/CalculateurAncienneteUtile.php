<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Models\Absence;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Ancienneté utile d'un salarié (CCIT art. 40 ; Code art. 46, 65).
 * - L'ancienneté court depuis l'embauche, période d'essai comprise.
 * - Les absences dont le type de congé a un impact « suspendue » sur l'ancienneté sont déduites
 *   (c'est le cas, à paramétrer par type, du service militaire et des absences non justifiées).
 * - La maladie est comptée pendant N mois, puis jusqu'au remplacement du salarié.
 * - Les absences pour raisons personnelles sont comptées dans la limite paramétrée.
 */
class CalculateurAncienneteUtile
{
    public function __construct(private ParametresAvancement $parametres) {}

    // Années révolues d'ancienneté utile à la date donnée.
    public function annees(Salarie $salarie, CarbonInterface $date): int
    {
        $corrigee = $this->dateCorrigee($salarie, $date);

        return $corrigee && $corrigee->lte($date) ? (int) $corrigee->diffInYears(Carbon::parse($date)->startOfDay()) : 0;
    }

    // Date d'embauche décalée des jours déduits : l'ancienneté se mesure depuis cette date.
    public function dateCorrigee(Salarie $salarie, CarbonInterface $date): ?Carbon
    {
        if (! $salarie->date_embauche) {
            return null;
        }

        $debut = Carbon::parse($salarie->date_embauche)->startOfDay();

        return $debut->copy()->addDays($this->joursExclus($salarie, $debut, Carbon::parse($date)->startOfDay()));
    }

    /**
     * Nombre de jours déduits de l'ancienneté entre deux dates (bornes incluses).
     */
    public function joursExclus(Salarie $salarie, CarbonInterface $du, CarbonInterface $au): int
    {
        $du = Carbon::parse($du)->startOfDay();
        $au = Carbon::parse($au)->startOfDay();
        if ($au->lt($du)) {
            return 0;
        }

        $codesMaladie = (array) $this->parametres->get('codes_maladie_anciennete');
        $codesPersonnels = (array) $this->parametres->get('codes_absences_personnelles');
        $moisMaladie = (int) $this->parametres->get('maladie_duree_comptee_mois');

        $absences = Absence::withoutGlobalScopes()
            ->where('salarie_id', $salarie->id)
            ->where('debut_le', '<=', $au->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('fin_le')->orWhere('fin_le', '>=', $du))
            ->with(['typeConge' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderBy('debut_le')
            ->get();

        $intervalles = [];
        $joursPersonnels = [];

        foreach ($absences as $absence) {
            $type = $absence->typeConge;
            if (! $type) {
                continue;
            }

            $debut = Carbon::parse($absence->debut_le)->startOfDay();
            $fin = $absence->fin_le ? Carbon::parse($absence->fin_le)->startOfDay() : $au->copy();

            if (in_array($type->code, $codesMaladie, true)) {
                // Comptée N mois, puis jusqu'au remplacement : seule la période après le remplacement est déduite.
                if ($absence->date_remplacement) {
                    $finComptee = $debut->copy()->addMonthsNoOverflow($moisMaladie)->subDay();
                    $exclusion = Carbon::parse($absence->date_remplacement)->startOfDay()->max($finComptee->addDay());
                    $intervalles[] = [$exclusion, $fin];
                }

                continue;
            }

            if (in_array($type->code, $codesPersonnels, true)) {
                // Comptées dans la limite de N jours par période ; le dépassement est déduit.
                $finBoucle = ($fin->lt($au) ? $fin : $au)->copy();
                for ($jour = ($debut->gt($du) ? $debut : $du)->copy(); $jour->lte($finBoucle); $jour->addDay()) {
                    $cle = $this->parametres->get('absences_personnelles_periode') === 'annee' ? $jour->year : 'carriere';
                    $joursPersonnels[$cle] = ($joursPersonnels[$cle] ?? 0) + 1;
                    if ($joursPersonnels[$cle] > (int) $this->parametres->get('absences_personnelles_limite_jours')) {
                        $intervalles[] = [$jour->copy(), $jour->copy()];
                    }
                }

                continue;
            }

            if ($type->impact_anciennete === ImpactAnciennete::SUSPENDUE) {
                $intervalles[] = [$debut, $fin];
            }
        }

        return $this->compterJours($intervalles, $du, $au);
    }

    /**
     * Jours couverts par l'union des intervalles, limités à [du, au].
     *
     * @param  list<array{0: Carbon, 1: Carbon}>  $intervalles
     */
    private function compterJours(array $intervalles, Carbon $du, Carbon $au): int
    {
        $bornes = [];
        foreach ($intervalles as [$debut, $fin]) {
            $debut = ($debut->gt($du) ? $debut : $du)->copy();
            $fin = ($fin->lt($au) ? $fin : $au)->copy();
            if ($debut->lte($fin)) {
                $bornes[] = [$debut, $fin];
            }
        }

        usort($bornes, fn ($a, $b) => $a[0] <=> $b[0]);

        $total = 0;
        $courant = null;
        foreach ($bornes as [$debut, $fin]) {
            if ($courant && $debut->lte($courant[1]->copy()->addDay())) {
                $courant[1] = ($fin->gt($courant[1]) ? $fin : $courant[1])->copy();

                continue;
            }
            if ($courant) {
                $total += (int) $courant[0]->diffInDays($courant[1]) + 1;
            }
            $courant = [$debut, $fin];
        }
        if ($courant) {
            $total += (int) $courant[0]->diffInDays($courant[1]) + 1;
        }

        return $total;
    }
}
