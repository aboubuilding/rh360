<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\JourFerie;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendrier de l'entreprise : jours fériés et jours ouvrés, pour les délais exprimés en jours
 * ouvrés (convocations syndicales) et le décompte des congés.
 */
class CalendrierJoursFeries
{
    /** @var array<int, array<string, string>> dates fériées (Y-m-d => libellé) par entreprise */
    private array $feries = [];

    public function __construct(private ParametresDemandes $parametres) {}

    public function estFerie(Carbon $date, int $entrepriseId): bool
    {
        return isset($this->feries($entrepriseId)[$date->toDateString()]);
    }

    // Jour travaillé dans l'entreprise : jour de semaine ouvré et non férié.
    public function estOuvre(Carbon $date, int $entrepriseId): bool
    {
        $dernierJourOuvre = $this->parametres->pourEntreprise($entrepriseId)->get('jours_ouvres') === 'lun_sam'
            ? Carbon::SATURDAY
            : Carbon::FRIDAY;

        return $date->dayOfWeekIso <= $dernierJourOuvre && ! $this->estFerie($date, $entrepriseId);
    }

    // Nombre de jours ouvrés strictement compris entre deux dates (bornes exclues).
    public function joursOuvresEntre(Carbon $du, Carbon $au, int $entrepriseId): int
    {
        $jours = 0;
        for ($jour = $du->copy()->startOfDay()->addDay(); $jour->lt($au->copy()->startOfDay()); $jour->addDay()) {
            if ($this->estOuvre($jour, $entrepriseId)) {
                $jours++;
            }
        }

        return $jours;
    }

    // Date atteinte en avançant de N jours ouvrés.
    public function ajouterJoursOuvres(Carbon $date, int $jours, int $entrepriseId): Carbon
    {
        $courant = $date->copy()->startOfDay();
        while ($jours > 0) {
            $courant->addDay();
            if ($this->estOuvre($courant, $entrepriseId)) {
                $jours--;
            }
        }

        return $courant;
    }

    /** @return Collection<int, JourFerie> jours fériés compris entre deux dates (bornes incluses) */
    public function feriesEntre(Carbon $du, Carbon $au, int $entrepriseId): Collection
    {
        return JourFerie::pourEntreprise($entrepriseId)
            ->whereBetween('date', [$du->toDateString(), $au->toDateString()])
            ->orderBy('date')
            ->get();
    }

    /**
     * Ajoute au calendrier les fêtes à date fixe et les fêtes chrétiennes mobiles de l'année,
     * marquées « proposé » pour vérification. Les fêtes musulmanes, dont la date est annoncée
     * chaque année, et les jours décrétés sont saisis par les RH. Les dates déjà présentes
     * ne sont pas modifiées.
     */
    public function proposer(int $annee, int $entrepriseId): int
    {
        $paques = self::paques($annee);
        $fetes = [
            "{$annee}-01-01" => 'Jour de l\'an',
            $paques->copy()->addDay()->toDateString() => 'Lundi de Pâques',
            "{$annee}-04-27" => 'Fête de l\'indépendance',
            "{$annee}-05-01" => 'Fête du travail',
            $paques->copy()->addDays(39)->toDateString() => 'Ascension',
            $paques->copy()->addDays(50)->toDateString() => 'Lundi de Pentecôte',
            "{$annee}-06-21" => 'Journée des martyrs',
            "{$annee}-08-15" => 'Assomption',
            "{$annee}-11-01" => 'Toussaint',
            "{$annee}-12-25" => 'Noël',
        ];

        $existantes = JourFerie::pourEntreprise($entrepriseId)->whereYear('date', $annee)
            ->pluck('date')->map(fn ($d) => $d->toDateString())->all();

        $ajoutes = 0;
        foreach ($fetes as $date => $libelle) {
            if (in_array($date, $existantes, true)) {
                continue;
            }
            JourFerie::create([
                'entreprise_id' => $entrepriseId,
                'date' => $date,
                'libelle' => $libelle,
                'origine' => 'propose',
                'cree_par' => auth()->id(),
            ]);
            $ajoutes++;
        }

        unset($this->feries[$entrepriseId]);

        return $ajoutes;
    }

    // Dimanche de Pâques (calendrier grégorien, algorithme de Meeus).
    public static function paques(int $annee): Carbon
    {
        $a = $annee % 19;
        $b = intdiv($annee, 100);
        $c = $annee % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mois = intdiv($h + $l - 7 * $m + 114, 31);
        $jour = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($annee, $mois, $jour)->startOfDay();
    }

    public function oublier(int $entrepriseId): void
    {
        unset($this->feries[$entrepriseId]);
    }

    /** @return array<string, string> */
    private function feries(int $entrepriseId): array
    {
        return $this->feries[$entrepriseId] ??= JourFerie::pourEntreprise($entrepriseId)->get()
            ->mapWithKeys(fn (JourFerie $j) => [$j->date->toDateString() => $j->libelle])
            ->all();
    }
}
