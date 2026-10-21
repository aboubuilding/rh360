<?php

namespace App\Domain\Paie\Services;

use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CalculateurAllocationConge;
use App\Domain\Conges\Services\ParametresDemandes;
use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prépare, pour une période de paie non figée, les saisies issues des absences du mois.
 *
 * - Absence non rémunérée (absence non justifiée, congé sans solde, grève…) : retenue
 *   proportionnelle au temps non travaillé (Code art. 160), jamais une sanction (Code art. 120).
 * - Congé payé : salaire des jours neutralisé, remplacé par l'allocation de congé (Code art. 202 ;
 *   CCIT art. 49 c), au moins égale au maintien du salaire si le paramètre le prévoit.
 * - Maladie : salaire des jours neutralisé, maintien à plein puis à demi-salaire selon le barème le
 *   plus favorable entre la CCIT (art. 16) et le Code (art. 69), apprécié pour l'arrêt entier.
 * - Maternité : salaire neutralisé, part employeur et, si l'employeur prépaie, avance de la part
 *   CNSS (Code art. 190 ; CCIT art. 49 d).
 * Les absences maintenues (permissions, enfant malade, activités syndicales…) n'ont pas de ligne.
 *
 * Valeur d'une journée : (salaire de base + prime d'ancienneté) / diviseur du paramétrage (30).
 * Les saisies portent la source « absences » et sont recalculées à chaque calcul de la période.
 */
class PreparateurPaieAbsences
{
    public const SOURCE = 'absences';

    private const STATUTS_EFFECTIFS = [
        StatutDemandeConge::AUTORISEE, StatutDemandeConge::PROGRAMMEE,
        StatutDemandeConge::EN_COURS, StatutDemandeConge::REPRISE_CONFIRMEE,
    ];

    /**
     * Rubriques des absences : neutralisations (montant négatif sur le brut) et maintiens.
     *
     * @return array<string, array{nom: string, imposable: bool, soumis_cotisation: bool}>
     */
    public static function rubriquesNecessaires(): array
    {
        return [
            'ABS-RET' => ['nom' => 'Retenue pour absence non rémunérée', 'imposable' => true, 'soumis_cotisation' => true],
            'CONGE-RET' => ['nom' => 'Jours de congé payé (salaire neutralisé)', 'imposable' => true, 'soumis_cotisation' => true],
            'CONGE-ALLOC' => ['nom' => 'Allocation de congé payé', 'imposable' => true, 'soumis_cotisation' => true],
            'MAL-RET' => ['nom' => 'Jours d\'arrêt maladie (salaire neutralisé)', 'imposable' => true, 'soumis_cotisation' => true],
            'MAL-PLEIN' => ['nom' => 'Maintien maladie à plein salaire', 'imposable' => true, 'soumis_cotisation' => true],
            'MAL-DEMI' => ['nom' => 'Maintien maladie à demi-salaire', 'imposable' => true, 'soumis_cotisation' => true],
            'MAT-RET' => ['nom' => 'Jours de congé de maternité (salaire neutralisé)', 'imposable' => true, 'soumis_cotisation' => true],
            'MAT-EMP' => ['nom' => 'Indemnité de maternité (part employeur)', 'imposable' => true, 'soumis_cotisation' => true],
            'MAT-AVANCE' => ['nom' => 'Avance de la part CNSS de l\'indemnité de maternité', 'imposable' => false, 'soumis_cotisation' => false],
        ];
    }

    /**
     * Crée les rubriques des absences manquantes d'une entreprise (idempotent).
     */
    public static function installerRubriques(int $entrepriseId): void
    {
        foreach (self::rubriquesNecessaires() as $code => $r) {
            RubriquePaie::withoutGlobalScopes()->firstOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $code],
                [
                    'nom' => $r['nom'],
                    'nature' => \App\Domain\Paie\Enums\NatureRubrique::GAIN->value,
                    'recurrence' => \App\Domain\Paie\Enums\RecurrenceRubrique::VARIABLE->value,
                    'mode_calcul' => \App\Domain\Paie\Enums\ModeCalculRubrique::MONTANT_FIXE->value,
                    'imposable' => $r['imposable'],
                    'traitement_fiscal' => ($r['imposable'] ? \App\Domain\Paie\Enums\TraitementFiscal::IMPOSABLE : \App\Domain\Paie\Enums\TraitementFiscal::EXONERE)->value,
                    'pourcentage_imposable' => $r['imposable'] ? 100 : 0,
                    'soumis_cotisation' => $r['soumis_cotisation'],
                    'est_frais' => false,
                    'actif' => true,
                    'etat' => 1,
                ],
            );
        }
    }

    public function __construct(
        private CalculateurAnciennete $anciennete,
        private CalculateurAncienneteUtile $ancienneteUtile,
        private CalculateurAllocationConge $allocation,
        private ParametresDemandes $parametres,
    ) {}

    /**
     * @return array<int, array{salarie_id: int, message: string}> anomalies (rubrique manquante…)
     */
    public function preparer(PeriodePaie $periode): array
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Période validée : les absences ne peuvent plus y être reportées.');
        }

        $this->parametres->pourEntreprise($periode->entreprise_id);
        $debut = Carbon::create($periode->annee, $periode->mois, 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth()->startOfDay();
        $rubriques = RubriquePaie::pourEntreprise($periode->entreprise_id)->pluck('id', 'code');
        $anomalies = [];

        DB::transaction(function () use ($periode, $debut, $fin, $rubriques, &$anomalies) {
            SaisiePaie::where('periode_id', $periode->id)->where('source', self::SOURCE)->delete();

            $salaries = Salarie::where('entreprise_id', $periode->entreprise_id)->where('actif', true)->get();
            foreach ($salaries as $salarie) {
                // Une saisie par rubrique : les absences de même nature du mois sont cumulées.
                $cumuls = [];
                foreach ($this->lignes($salarie, $debut, $fin) as [$code, $montant, $quantite, $observations]) {
                    $cumuls[$code][0] = ($cumuls[$code][0] ?? 0) + $montant;
                    $cumuls[$code][1] = ($cumuls[$code][1] ?? 0) + $quantite;
                    $cumuls[$code][2][] = $observations;
                }

                foreach ($cumuls as $code => [$montant, $quantite, $observations]) {
                    $observations = mb_substr(implode(' ; ', $observations), 0, 1000);
                    if (! isset($rubriques[$code])) {
                        $anomalies[] = ['salarie_id' => $salarie->id, 'message' => "Rubrique {$code} introuvable : l'absence n'a pas été reportée en paie."];

                        continue;
                    }
                    // Une saisie manuelle sur la même rubrique n'est jamais écrasée.
                    if (SaisiePaie::where('periode_id', $periode->id)->where('salarie_id', $salarie->id)->where('rubrique_id', $rubriques[$code])->exists()) {
                        $anomalies[] = ['salarie_id' => $salarie->id, 'message' => "Une saisie manuelle existe déjà sur la rubrique {$code} : l'absence de ".number_format($montant, 0, ',', ' ').' FCFA n\'a pas été reportée.'];

                        continue;
                    }
                    (new SaisiePaie)->forceFill([
                        'entreprise_id' => $periode->entreprise_id,
                        'periode_id' => $periode->id,
                        'salarie_id' => $salarie->id,
                        'rubrique_id' => $rubriques[$code],
                        'quantite' => $quantite,
                        'taux' => 0,
                        'montant' => round($montant, 2),
                        'observations' => $observations,
                        'source' => self::SOURCE,
                        'cree_par' => auth()->id(),
                        'etat' => 1,
                    ])->save();
                }
            }
        });

        return $anomalies;
    }

    /**
     * Lignes d'absence d'un salarié pour le mois : [code, montant, jours, observations].
     *
     * @return array<int, array{0: string, 1: float, 2: float, 3: string}>
     */
    public function lignes(Salarie $salarie, Carbon $debut, Carbon $fin): array
    {
        $journalier = $this->salaireJournalier($salarie, $fin);
        if ($journalier <= 0) {
            return [];
        }

        $lignes = [];
        $demandes = DemandeConge::pourEntreprise($salarie->entreprise_id)->with('typeConge')
            ->where('salarie_id', $salarie->id)
            ->whereIn('statut', array_map(fn ($s) => $s->value, self::STATUTS_EFFECTIFS))
            ->where('date_debut', '<=', $fin->toDateString())
            ->where('date_reprise', '>', $debut->toDateString())
            ->orderBy('date_debut')
            ->get();

        foreach ($demandes as $demande) {
            $type = $demande->typeConge;
            if (! $type || $type->unite === UniteConge::HEURE) {
                continue;
            }
            $jours = $this->joursDansLeMois($demande->date_debut, $demande->date_reprise->copy()->subDay(), $debut, $fin);
            if ($jours <= 0) {
                continue;
            }
            $libelle = "{$type->nom} ({$demande->numero_demande}), {$this->nombre($jours)} jour(s)";

            if ($type->famille === FamilleDemande::CONGE_PAYE) {
                $parJour = $this->allocation->calculer($demande)['par_jour'] ?? null;
                if ($parJour === null || ($this->parametres->get('conge_comparaison_maintien') && $parJour < $journalier)) {
                    $parJour = $journalier;
                }
                $lignes[] = ['CONGE-RET', -$jours * $journalier, $jours, $libelle];
                $lignes[] = ['CONGE-ALLOC', $jours * $parJour, $jours, $libelle];
            } elseif ($type->famille === FamilleDemande::MALADIE) {
                [$pleins, $demis] = $this->joursMaladie($salarie, $demande, $debut, $fin);
                // Le maintien ne porte jamais sur plus de jours que ceux neutralisés.
                $pleins = min($pleins, $jours);
                $demis = min($demis, $jours - $pleins);
                $lignes[] = ['MAL-RET', -$jours * $journalier, $jours, $libelle];
                if ($pleins > 0) {
                    $lignes[] = ['MAL-PLEIN', $pleins * $journalier, $pleins, "{$libelle}, dont {$this->nombre($pleins)} à plein salaire"];
                }
                if ($demis > 0) {
                    $lignes[] = ['MAL-DEMI', $demis * $journalier * 0.5, $demis, "{$libelle}, dont {$this->nombre($demis)} à demi-salaire"];
                }
            } elseif ($type->famille === FamilleDemande::MATERNITE) {
                $lignes[] = ['MAT-RET', -$jours * $journalier, $jours, $libelle];
                $lignes[] = ['MAT-EMP', $jours * $journalier * 0.5, $jours, $libelle];
                if ($this->parametres->get('maternite_maintien_integral') && $this->parametres->get('maternite_prepaiement')) {
                    $lignes[] = ['MAT-AVANCE', $jours * $journalier * 0.5, $jours, "{$libelle} : part de la CNSS avancée par l'employeur"];
                }
            } elseif ($type->traitement_salarial === TraitementSalarial::NON_REMUNERE) {
                $lignes[] = ['ABS-RET', -$jours * $journalier, $jours, $libelle];
            }
        }

        // Absences constatées hors demande, non rémunérées.
        $absences = Absence::pourEntreprise($salarie->entreprise_id)->with('typeConge')
            ->where('salarie_id', $salarie->id)
            ->whereNotIn('id', DemandeConge::pourEntreprise($salarie->entreprise_id)->whereNotNull('absence_id')->select('absence_id'))
            ->where('debut_le', '<=', $fin->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('fin_le')->orWhere('fin_le', '>=', $debut))
            ->get();
        foreach ($absences as $absence) {
            if ($absence->typeConge?->traitement_salarial !== TraitementSalarial::NON_REMUNERE) {
                continue;
            }
            $jours = $this->joursDansLeMois($absence->debut_le->copy()->startOfDay(), ($absence->fin_le ?? $fin)->copy()->startOfDay(), $debut, $fin);
            if ($jours > 0) {
                $lignes[] = ['ABS-RET', -$jours * $journalier, $jours, "{$absence->typeConge->nom} du {$absence->debut_le->format('d/m/Y')}, {$this->nombre($jours)} jour(s)"];
            }
        }

        return $lignes;
    }

    /**
     * Jours d'arrêt maladie du mois indemnisés à plein et à demi-salaire. Le barème le plus favorable
     * entre la CCIT et le Code est retenu pour l'arrêt entier, selon l'ancienneté à son début.
     *
     * @return array{0: float, 1: float}
     */
    private function joursMaladie(Salarie $salarie, DemandeConge $demande, Carbon $debut, Carbon $fin): array
    {
        $annees = $this->ancienneteUtile->annees($salarie, $demande->date_debut);
        $ccit = match (true) {
            $annees < 1 => [30, 0],
            $annees < 5 => [30, 90],
            $annees < 10 => [60, 120],
            default => [120, 60],
        };
        $code = [5, match (true) {
            $annees < 1 => 30,
            $annees < 5 => 60,
            default => 120,
        }];
        [$pleins, $demis] = ($ccit[0] + $ccit[1] / 2) >= ($code[0] + $code[1] / 2) ? $ccit : $code;

        // Rang, dans l'arrêt, des jours qui tombent dans le mois.
        $premier = $demande->date_debut->copy()->max($debut)->copy();
        $dernier = $demande->date_reprise->copy()->subDay()->min($fin)->copy();
        $rangDebut = (int) $demande->date_debut->diffInDays($premier);
        $rangFin = (int) $demande->date_debut->diffInDays($dernier);

        $nbPleins = max(0, min($rangFin, $pleins - 1) - $rangDebut + 1);
        $nbDemis = max(0, min($rangFin, $pleins + $demis - 1) - max($rangDebut, $pleins) + 1);

        return [(float) $nbPleins, (float) $nbDemis];
    }

    // Jours calendaires d'une absence compris dans le mois ; un mois entier compte pour le diviseur (30).
    private function joursDansLeMois(Carbon $du, Carbon $au, Carbon $debut, Carbon $fin): float
    {
        $premier = $du->copy()->max($debut)->copy();
        $dernier = $au->copy()->min($fin)->copy();
        if ($dernier->lt($premier)) {
            return 0.0;
        }

        $jours = (int) $premier->diffInDays($dernier) + 1;
        $diviseur = (int) $this->parametres->get('salaire_journalier_diviseur');

        return (float) ($jours >= $debut->daysInMonth ? $diviseur : min($jours, $diviseur));
    }

    private function salaireJournalier(Salarie $salarie, Carbon $date): float
    {
        $base = (float) ElementPaieSalarie::where('salarie_id', $salarie->id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');
        if ($base <= 0) {
            return 0.0;
        }

        $mensuel = $base + $this->anciennete->calculerPrime($salarie, $base, $date);

        return $mensuel / (int) $this->parametres->get('salaire_journalier_diviseur');
    }

    private function nombre(float $valeur): string
    {
        return rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');
    }
}
