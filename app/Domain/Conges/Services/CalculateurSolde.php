<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

class CalculateurSolde
{
    /**
     * Calcule ou récupère le solde d'un salarié pour un type et une année.
     * Crée le solde si nécessaire avec un report automatique de l'année précédente.
     */
    public function obtenir(Salarie $salarie, TypeConge $type, int $annee): SoldeConge
    {
        $solde = SoldeConge::where('salarie_id', $salarie->id)
            ->where('type_conge_id', $type->id)
            ->where('annee', $annee)
            ->first();

        if ($solde) return $solde;

        return $this->creer($salarie, $type, $annee);
    }

    /**
     * Crée un solde pour un salarié, en récupérant le report de l'année N-1.
     */
    public function creer(Salarie $salarie, TypeConge $type, int $annee): SoldeConge
    {
        $report = $this->calculerReport($salarie, $type, $annee - 1);
        $acquis = $this->calculerAcquisition($salarie, $type, $annee);

        return SoldeConge::create([
            'entreprise_id' => $salarie->entreprise_id,
            'salarie_id' => $salarie->id,
            'type_conge_id' => $type->id,
            'annee' => $annee,
            'solde_ouverture' => $report,
            'acquis' => $acquis,
            'ajustement' => 0,
            'consomme' => 0,
            'reserve' => 0,
            'etat' => 1,
        ]);
    }

    /**
     * Report de l'année précédente : solde disponible restant.
     */
    public function calculerReport(Salarie $salarie, TypeConge $type, int $annee): float
    {
        $soldePrecedent = SoldeConge::where('salarie_id', $salarie->id)
            ->where('type_conge_id', $type->id)
            ->where('annee', $annee)
            ->first();

        if (! $soldePrecedent) return 0;

        // Le report est plafonné par la politique de l'entreprise
        // (pour l'instant, report intégral du disponible)
        return max(0, $soldePrecedent->disponible);
    }

    /**
     * Acquisition annuelle selon le droit annuel et le prorata d'embauche.
     */
    public function calculerAcquisition(Salarie $salarie, TypeConge $type, int $annee): float
    {
        $droitAnnuel = (float) $type->droit_annuel;
        if ($droitAnnuel === 0.0) return 0;

        $dateEmbauche = $salarie->date_embauche;
        if (! $dateEmbauche) return $droitAnnuel;

        $anneeDebut = Carbon::createFromDate($annee, 1, 1);
        $anneeFin = Carbon::createFromDate($annee, 12, 31);

        // Si le salarié a été embauché avant l'année → droit complet
        if ($dateEmbauche->lessThanOrEqualTo($anneeDebut)) {
            return $droitAnnuel;
        }

        // Si embauché pendant l'année → prorata temporis
        if ($dateEmbauche->greaterThan($anneeFin)) {
            return 0;
        }

        $joursTravailles = $dateEmbauche->diffInDays($anneeFin) + 1;
        $joursAnnee = 365;

        return round($droitAnnuel * ($joursTravailles / $joursAnnee), 2);
    }

    /**
     * Recalcule le solde consommé et réservé depuis les demandes actives.
     */
    public function resynchroniser(SoldeConge $solde): SoldeConge
    {
        $demandes = \App\Domain\Conges\Models\DemandeConge::where('salarie_id', $solde->salarie_id)
            ->where('type_conge_id', $solde->type_conge_id)
            ->whereYear('date_debut', $solde->annee)
            ->get();

        $reserve = $demandes
            ->filter(fn ($d) => $d->statut->reserveDesJours())
            ->sum('duree_jours');

        $consomme = $demandes
            ->filter(fn ($d) => $d->statut->consommeDesJours())
            ->sum('duree_jours');

        $solde->update([
            'reserve' => $reserve,
            'consomme' => $consomme,
        ]);

        return $solde->fresh();
    }

    /**
     * Vérifie que le solde est suffisant pour une demande.
     */
    public function verifierDisponibilite(SoldeConge $solde, float $jours): bool
    {
        return $solde->disponible >= $jours;
    }
}