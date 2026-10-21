<?php

namespace App\Domain\Conges\Services;

use App\Domain\Carriere\Services\CalculateurAncienneteUtile;
use App\Domain\Conges\Enums\BasePlafond;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Enums\LienParente;
use Carbon\Carbon;

/**
 * Contrôles bloquants d'une demande, à la soumission et à l'autorisation : ancienneté minimale,
 * délai de dépôt, événement et durée de la permission, enfant concerné, plafond, prise en
 * continu du crédit d'heures, durée en heures et chevauchement avec une autre absence.
 */
class ControleDemande
{
    public function __construct(
        private CompteurDemandes $compteur,
        private CalendrierJoursFeries $calendrier,
        private ParametresDemandes $parametres,
        private CalculateurAncienneteUtile $anciennete,
    ) {}

    /**
     * @return array<int, string> messages des règles non respectées (vide si la demande est recevable)
     */
    public function verifier(DemandeConge $demande): array
    {
        $type = $demande->typeConge;
        if (! $type || ! $demande->date_debut) {
            return [];
        }
        $this->parametres->pourEntreprise($demande->entreprise_id);

        return array_values(array_filter([
            $this->heures($demande, $type),
            $this->droitCongePaye($demande, $type),
            $this->ancienneteMinimale($demande, $type),
            $this->delaiDepot($demande, $type),
            ...$this->evenement($demande, $type),
            ...$this->enfant($demande, $type),
            $this->plafond($demande, $type),
            $this->priseEnContinu($demande, $type),
            $this->chevauchement($demande),
        ]));
    }

    // Lève une exception regroupant les règles non respectées.
    public function exiger(DemandeConge $demande): void
    {
        $erreurs = $this->verifier($demande);
        if ($erreurs) {
            throw new \DomainException(implode(' ', $erreurs));
        }
    }

    private function heures(DemandeConge $demande, TypeConge $type): ?string
    {
        if ($type->unite === UniteConge::HEURE && (float) $demande->duree_heures <= 0) {
            return 'Indiquez la durée de l\'absence en heures.';
        }

        return null;
    }

    // Congé payé : aucun droit avant 6 mois de service consécutifs (anticipation, d'accord parties).
    private function droitCongePaye(DemandeConge $demande, TypeConge $type): ?string
    {
        if (! GrandLivreConges::gere($type) || ! $demande->salarie->date_embauche) {
            return null;
        }

        $ouverture = $demande->salarie->date_embauche->copy()
            ->addMonthsNoOverflow((int) $this->parametres->get('conge_anciennete_anticipation_mois'));
        if ($demande->date_debut->lt($ouverture)) {
            return sprintf(
                'Le droit au congé payé s\'ouvre après %d mois de service consécutifs, soit à partir du %s.',
                (int) $this->parametres->get('conge_anciennete_anticipation_mois'), $ouverture->format('d/m/Y'),
            );
        }

        return null;
    }

    // Ancienneté utile à la date de début (5 ans pour un congé sans solde, 6 mois pour une assemblée syndicale).
    private function ancienneteMinimale(DemandeConge $demande, TypeConge $type): ?string
    {
        if (! $type->anciennete_min_mois) {
            return null;
        }

        $depuis = $this->anciennete->dateCorrigee($demande->salarie, $demande->date_debut) ?? $demande->salarie->date_embauche;
        if (! $depuis) {
            return 'La date d\'embauche du salarié est inconnue : l\'ancienneté minimale ne peut pas être vérifiée.';
        }

        $mois = (int) floor($depuis->diffInMonths($demande->date_debut, false));
        if ($mois < $type->anciennete_min_mois) {
            return sprintf(
                'Ancienneté insuffisante : %s exigé(s), %s à la date de début.',
                $this->libelleMois($type->anciennete_min_mois),
                $this->libelleMois(max(0, $mois)),
            );
        }

        return null;
    }

    // Délai entre le dépôt de la demande et le début de l'absence, sauf force majeure.
    private function delaiDepot(DemandeConge $demande, TypeConge $type): ?string
    {
        if (! $type->delai_depot || $demande->force_majeure) {
            return null;
        }

        $depot = ($demande->date_demande ?? today())->copy()->startOfDay();
        $debut = $demande->date_debut->copy()->startOfDay();

        // Date limite de dépôt : N jours (ouvrés ou calendaires) avant le début de l'absence.
        $ouvres = $type->delai_depot_unite === 'ouvres';
        $limite = $ouvres
            ? $this->calendrier->retrancherJoursOuvres($debut, $type->delai_depot, $demande->entreprise_id)
            : $debut->copy()->subDays($type->delai_depot);

        if ($depot->gt($limite)) {
            return sprintf(
                'Demande déposée trop tard : elle devait l\'être %d %s avant le début de l\'absence, soit au plus tard le %s, sauf force majeure.',
                $type->delai_depot, $ouvres ? 'jour(s) ouvré(s)' : 'jour(s)', $limite->format('d/m/Y'),
            );
        }

        return null;
    }

    /** @return array<int, ?string> événement requis, durée et délai de prise */
    private function evenement(DemandeConge $demande, TypeConge $type): array
    {
        if (! $type->evenements()->exists()) {
            return [];
        }

        $evenement = $demande->evenement;
        if (! $evenement) {
            return ['Précisez l\'événement à l\'origine de la permission.'];
        }
        if (! $demande->date_evenement) {
            return ['Précisez la date de l\'événement.'];
        }

        $erreurs = [];
        $deja = $this->compteurEvenement($demande);
        if ($deja + (float) $demande->duree_jours > (float) $evenement->jours) {
            $erreurs[] = sprintf(
                'Durée trop longue pour « %s » : %s jour(s) au plus%s.',
                $evenement->libelle,
                $this->nombre((float) $evenement->jours),
                $deja > 0 ? ', dont '.$this->nombre($deja).' déjà accordé(s)' : '',
            );
        }

        if ($evenement->delai_prise_mois) {
            $limite = $demande->date_evenement->copy()->addMonthsNoOverflow($evenement->delai_prise_mois);
            if ($demande->date_debut->gt($limite)) {
                $erreurs[] = sprintf(
                    'Permission à prendre dans les %d mois suivant l\'événement, soit au plus tard le %s.',
                    $evenement->delai_prise_mois, $limite->format('d/m/Y'),
                );
            }
        }

        return $erreurs;
    }

    /** @return array<int, ?string> enfant désigné, à charge et en âge d'ouvrir droit */
    private function enfant(DemandeConge $demande, TypeConge $type): array
    {
        if ($type->plafond_base !== BasePlafond::ENFANT) {
            return [];
        }

        $enfant = $demande->membreFoyer;
        if (! $enfant || $enfant->salarie_id !== $demande->salarie_id || $enfant->lien_parente !== LienParente::ENFANT->value) {
            return ['Désignez l\'enfant concerné parmi les enfants déclarés du salarié.'];
        }

        $erreurs = [];
        $date = $demande->date_debut;
        $aCharge = $enfant->est_a_charge
            && (! $enfant->date_debut_charge || $enfant->date_debut_charge->lte($date))
            && (! $enfant->date_fin_charge || $enfant->date_fin_charge->gte($date));
        if (! $aCharge) {
            $erreurs[] = "L'enfant {$enfant->prenoms} n'est pas déclaré à la charge du salarié.";
        }

        $ageMax = (int) $this->parametres->get('enfant_malade_age_max');
        if (! $enfant->date_naissance) {
            $erreurs[] = "La date de naissance de l'enfant {$enfant->prenoms} est inconnue.";
        } elseif ((int) $enfant->date_naissance->diffInYears($date) > $ageMax) {
            $erreurs[] = sprintf('L\'enfant %s a %d ans : au-delà de %d ans révolus, l\'absence n\'est pas prévue.', $enfant->prenoms, (int) $enfant->date_naissance->diffInYears($date), $ageMax);
        }

        return $erreurs;
    }

    private function plafond(DemandeConge $demande, TypeConge $type): ?string
    {
        $situation = $this->compteur->situation($demande);
        if (! $situation || $situation['reste'] >= 0) {
            return null;
        }

        return sprintf(
            'Plafond dépassé : %s %s %s%s, %s déjà décompté(s), %s demandé(s).',
            $this->nombre($situation['plafond']),
            $situation['unite'],
            $situation['periode'],
            $type->plafond_base === BasePlafond::ENFANT ? ' pour cet enfant' : '',
            $this->nombre($situation['consomme']),
            $this->nombre($situation['demande']),
        );
    }

    // Crédit d'heures « ni pris en continu » : un maximum d'heures par jour ouvré de la demande.
    private function priseEnContinu(DemandeConge $demande, TypeConge $type): ?string
    {
        if ($type->prise_continue_autorisee || $type->unite !== UniteConge::HEURE || ! $demande->date_reprise) {
            return null;
        }

        $maxJour = (float) $this->parametres->get('syndical_credit_heures_max_jour');
        $veille = $demande->date_debut->copy()->subDay();
        $jours = max(1, $this->calendrier->joursOuvresEntre($veille, $demande->date_reprise, $demande->entreprise_id));
        if ((float) $demande->duree_heures > $maxJour * $jours) {
            return sprintf(
                'Ce crédit d\'heures ne se prend pas d\'un seul tenant : %s heure(s) par jour au plus, soit %s heure(s) sur %d jour(s) ouvré(s).',
                $this->nombre($maxJour), $this->nombre($maxJour * $jours), $jours,
            );
        }

        return null;
    }

    /**
     * Deux absences d'un même salarié ne se chevauchent pas. Les demandes en heures (délégation,
     * crédit syndical, allaitement) peuvent coexister entre elles dans une même journée.
     */
    private function chevauchement(DemandeConge $demande): ?string
    {
        if (! $demande->date_reprise) {
            return null;
        }
        $enHeures = $demande->typeConge->unite === UniteConge::HEURE;

        $autre = DemandeConge::pourEntreprise($demande->entreprise_id)
            ->with('typeConge')
            ->where('salarie_id', $demande->salarie_id)
            ->whereNotIn('statut', array_map(fn ($s) => $s->value, CompteurDemandes::STATUTS_NON_DECOMPTES))
            ->when($demande->exists, fn ($q) => $q->where('id', '!=', $demande->id))
            ->where('date_debut', '<', $demande->date_reprise->toDateString())
            ->where('date_reprise', '>', $demande->date_debut->toDateString())
            ->get()
            ->first(fn (DemandeConge $d) => ! ($enHeures && $d->typeConge?->unite === UniteConge::HEURE));

        if ($autre) {
            return sprintf(
                'Ces dates chevauchent la demande %s (%s, du %s au %s).',
                $autre->numero_demande,
                $autre->typeConge?->nom,
                $autre->date_debut->format('d/m/Y'),
                $autre->date_reprise->copy()->subDay()->format('d/m/Y'),
            );
        }

        return null;
    }

    // Jours déjà accordés pour le même événement (même date), hors cette demande.
    private function compteurEvenement(DemandeConge $demande): float
    {
        return (float) DemandeConge::pourEntreprise($demande->entreprise_id)
            ->where('salarie_id', $demande->salarie_id)
            ->where('evenement_permission_id', $demande->evenement_permission_id)
            ->where('date_evenement', $demande->date_evenement->toDateString())
            ->whereNotIn('statut', array_map(fn ($s) => $s->value, CompteurDemandes::STATUTS_NON_DECOMPTES))
            ->when($demande->exists, fn ($q) => $q->where('id', '!=', $demande->id))
            ->sum('duree_jours');
    }

    private function libelleMois(int $mois): string
    {
        if ($mois >= 12 && $mois % 12 === 0) {
            return ($mois / 12).' an(s)';
        }
        if ($mois >= 12) {
            return intdiv($mois, 12).' an(s) et '.($mois % 12).' mois';
        }

        return $mois.' mois';
    }

    private function nombre(float $valeur): string
    {
        return rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');
    }
}
