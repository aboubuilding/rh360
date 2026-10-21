<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Enums\BasePlafond;
use App\Domain\Conges\Enums\PeriodePlafond;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\DemandeConge;
use Carbon\Carbon;

/**
 * Compteurs des types de demande plafonnés : ce qui est déjà pris ou accordé sur la période du
 * plafond (10 jours d'enfant malade par an et par enfant, 10 jours de congrès par an, 30 heures
 * de crédit syndical et 15 heures de délégation par mois…).
 *
 * Une demande compte dans la période de sa date de début. Les demandes en brouillon, refusées,
 * annulées ou requalifiées ne comptent pas.
 */
class CompteurDemandes
{
    private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public const STATUTS_NON_DECOMPTES = [
        StatutDemandeConge::BROUILLON,
        StatutDemandeConge::REFUSEE,
        StatutDemandeConge::ANNULEE,
        StatutDemandeConge::REQUALIFIEE,
    ];

    /**
     * Situation du compteur pour une demande : plafond, déjà décompté (hors cette demande),
     * cette demande et reste après elle. Null si le type n'a pas de plafond.
     *
     * @return array{plafond: float, unite: string, consomme: float, demande: float, reste: float, periode: string}|null
     */
    public function situation(DemandeConge $demande): ?array
    {
        $type = $demande->typeConge;
        if (! $type?->aUnPlafond() || ! $demande->date_debut) {
            return null;
        }

        $plafond = (float) $type->plafond_valeur;
        $consomme = $this->consomme($demande);
        $quantite = $this->quantite($demande);

        return [
            'plafond' => $plafond,
            'unite' => $type->plafond_unite === 'heure' ? 'heure(s)' : 'jour(s)',
            'consomme' => $consomme,
            'demande' => $quantite,
            'reste' => round($plafond - $consomme - $quantite, 2),
            'periode' => $this->libellePeriode($demande),
        ];
    }

    // Déjà pris ou accordé sur la période du plafond, sans compter la demande elle-même.
    public function consomme(DemandeConge $demande): float
    {
        $type = $demande->typeConge;
        [$du, $au] = $this->fenetre($demande);

        $requete = DemandeConge::pourEntreprise($demande->entreprise_id)
            ->where('salarie_id', $demande->salarie_id)
            ->where('type_conge_id', $type->id)
            ->whereNotIn('statut', array_map(fn ($s) => $s->value, self::STATUTS_NON_DECOMPTES))
            ->when($demande->exists, fn ($q) => $q->where('id', '!=', $demande->id))
            ->when($du, fn ($q) => $q->where('date_debut', '>=', $du->toDateString()))
            ->when($au, fn ($q) => $q->where('date_debut', '<=', $au->toDateString()))
            ->when($type->plafond_base === BasePlafond::ENFANT, fn ($q) => $q->where('membre_foyer_id', $demande->membre_foyer_id))
            ->when($type->plafond_periode === PeriodePlafond::EVENEMENT, fn ($q) => $q
                ->where('evenement_permission_id', $demande->evenement_permission_id)
                ->where('date_evenement', $demande->date_evenement?->toDateString()));

        return round((float) $requete->sum($type->plafond_unite === 'heure' ? 'duree_heures' : 'duree_jours'), 2);
    }

    // Quantité de la demande dans l'unité du plafond.
    public function quantite(DemandeConge $demande): float
    {
        $heures = $demande->typeConge?->plafond_unite === 'heure' || $demande->typeConge?->unite === UniteConge::HEURE;

        return round((float) ($heures ? $demande->duree_heures : $demande->duree_jours), 2);
    }

    /**
     * Bornes de la période du plafond contenant la date de début de la demande.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function fenetre(DemandeConge $demande): array
    {
        $date = $demande->date_debut->copy();

        return match ($demande->typeConge->plafond_periode) {
            PeriodePlafond::ANNEE_CIVILE => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
            PeriodePlafond::MOIS => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            PeriodePlafond::ANNEE_SERVICE => $this->anneeDeService($demande, $date),
            default => [null, null],
        };
    }

    private function libellePeriode(DemandeConge $demande): string
    {
        [$du, $au] = $this->fenetre($demande);

        return match ($demande->typeConge->plafond_periode) {
            PeriodePlafond::ANNEE_CIVILE => 'en '.$du->year,
            PeriodePlafond::MOIS => 'en '.self::MOIS[$du->month - 1].' '.$du->year,
            PeriodePlafond::ANNEE_SERVICE => 'du '.$du->format('d/m/Y').' au '.$au->format('d/m/Y'),
            PeriodePlafond::EVENEMENT => 'pour cet événement',
            default => 'sur la carrière',
        };
    }

    /** @return array{0: Carbon, 1: Carbon} année de service (depuis la date anniversaire de l'embauche) */
    private function anneeDeService(DemandeConge $demande, Carbon $date): array
    {
        $embauche = $demande->salarie?->date_embauche;
        if (! $embauche) {
            return [$date->copy()->startOfYear(), $date->copy()->endOfYear()];
        }

        $debut = $embauche->copy()->setYear($date->year);
        if ($debut->gt($date)) {
            $debut->subYear();
        }

        return [$debut, $debut->copy()->addYear()->subDay()];
    }
}
