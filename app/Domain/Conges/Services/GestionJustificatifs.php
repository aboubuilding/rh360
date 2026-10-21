<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Actions\AjusterSoldeConge;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Events\DemandeAutorisee;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\HistoriqueDemande;
use App\Domain\Conges\Models\PieceDemande;
use App\Domain\Conges\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Justificatifs des demandes : échéance, dépôt et contrôle des pièces, orientation de la demande
 * (validation automatique d'une absence de droit, retour au valideur d'une absence soumise à
 * accord) et requalification d'une demande dont le justificatif n'a pas été remis à temps.
 *
 * Une absence de droit dont le justificatif est conforme ne peut pas être refusée : l'ERP
 * constate, il ne décide pas (CCIT art. 14, 50, 51 ; Code art. 190).
 */
class GestionJustificatifs
{
    public function __construct(
        private CircuitDemandeConge $circuit,
        private ParametresDemandes $parametres,
        private CalculateurSolde $calculateurSolde,
    ) {}

    /**
     * Date limite de remise du justificatif : délai du type, compté depuis l'événement
     * (permission exceptionnelle : « au plus tard huit jours après l'événement ») ou depuis
     * le premier jour d'absence (maladie : certificat dans les six jours).
     */
    public function echeance(DemandeConge $demande): ?Carbon
    {
        $type = $demande->typeConge;
        if (! $type?->justificatif_requis || $type->delai_justification_jours === null) {
            return null;
        }

        $depart = $type->delai_justification_depart === 'evenement'
            ? ($demande->date_evenement ?? $demande->date_debut)
            : $demande->date_debut;

        return $depart?->copy()->addDays($type->delai_justification_jours);
    }

    /**
     * Oriente une demande soumise ou en attente de justificatif :
     * - justificatif requis et non reçu conforme : en attente de justificatif ;
     * - absence de droit : validée automatiquement ;
     * - absence soumise à accord : transmise au valideur.
     */
    public function orienter(DemandeConge $demande): DemandeConge
    {
        $type = $demande->typeConge;
        $enAttente = in_array($demande->statut, [StatutDemandeConge::SOUMISE, StatutDemandeConge::ATTENTE_JUSTIFICATIF], true);
        if (! $type || ! $enAttente) {
            return $demande;
        }

        if ($type->necessiteJustificatif() && ! $demande->aUnePieceConforme()) {
            if ($demande->statut === StatutDemandeConge::SOUMISE) {
                $echeance = $demande->echeance_justificatif?->format('d/m/Y');

                return $this->circuit->transitionner(
                    $demande,
                    StatutDemandeConge::ATTENTE_JUSTIFICATIF,
                    $echeance ? "Justificatif attendu au plus tard le {$echeance}." : 'Justificatif attendu.',
                    automatique: true,
                );
            }

            return $demande;
        }

        if ($type->estDeDroit()) {
            $demande->update(['validation_automatique' => true, 'date_decision' => now()]);
            $demande = $this->circuit->transitionner(
                $demande,
                StatutDemandeConge::AUTORISEE,
                $type->necessiteJustificatif()
                    ? 'Absence de droit : validée sur justificatif conforme.'
                    : 'Absence de droit : validée sans justificatif requis.',
                automatique: true,
            );
            $this->qualifierAbsenceLiee($demande);
            event(new DemandeAutorisee($demande));

            return $demande;
        }

        if ($demande->statut === StatutDemandeConge::ATTENTE_JUSTIFICATIF) {
            return $this->circuit->transitionner(
                $demande,
                StatutDemandeConge::SOUMISE,
                'Justificatif reçu : demande transmise pour décision.',
                automatique: true,
            );
        }

        return $demande;
    }

    /**
     * Dépose un justificatif. Si les RH l'ont vérifié lors du dépôt, son contrôle est enregistré
     * dans le même temps.
     */
    public function deposer(DemandeConge $demande, UploadedFile $fichier, string $nature, ?bool $conforme = null, ?string $motifNonConforme = null): PieceDemande
    {
        if ($demande->statut === StatutDemandeConge::ANNULEE) {
            throw new \DomainException('La demande est annulée : aucun justificatif ne peut y être ajouté.');
        }

        $piece = PieceDemande::create([
            'entreprise_id' => $demande->entreprise_id,
            'demande_id' => $demande->id,
            'nature' => $nature,
            'chemin' => $fichier->store("conges/demandes/{$demande->id}", 'local'),
            'nom_original' => $fichier->getClientOriginalName(),
            'recue_le' => now(),
            'depose_par' => auth()->id(),
        ]);

        if ($conforme !== null) {
            $this->controler($piece, $conforme, $motifNonConforme);
        }

        return $piece->fresh();
    }

    // Contrôle d'un justificatif par les RH ; une pièce conforme relance l'orientation de la demande.
    public function controler(PieceDemande $piece, bool $conforme, ?string $motifNonConforme = null): PieceDemande
    {
        if (! $conforme && blank($motifNonConforme)) {
            throw new \DomainException('Précisez pourquoi le justificatif n\'est pas conforme.');
        }

        return DB::transaction(function () use ($piece, $conforme, $motifNonConforme) {
            $piece->update([
                'conforme' => $conforme,
                'motif_non_conforme' => $conforme ? null : $motifNonConforme,
                'controle_par' => auth()->id(),
                'controle_le' => now(),
            ]);

            if ($conforme) {
                $this->orienter($piece->demande()->first());
            }

            return $piece->fresh();
        });
    }

    /**
     * Requalifie une demande dont le justificatif n'a pas été remis conforme dans le délai :
     * - permission exceptionnelle : selon le paramétrage, absence non justifiée ou jours
     *   déduits du congé payé ;
     * - autres types : absence non justifiée.
     * Une pièce reçue après la requalification ne la remet pas en cause.
     */
    public function requalifier(DemandeConge $demande, string $motif, bool $automatique = false): DemandeConge
    {
        if ($demande->statut !== StatutDemandeConge::ATTENTE_JUSTIFICATIF) {
            throw new \DomainException('Seule une demande en attente de justificatif peut être requalifiée.');
        }

        $mode = $demande->typeConge?->famille === FamilleDemande::PERMISSION
            ? $this->parametres->pourEntreprise($demande->entreprise_id)->get('permissions_justificatif_tardif')
            : 'absence_non_justifiee';

        return DB::transaction(function () use ($demande, $motif, $automatique, $mode) {
            $texte = "Requalification de la demande {$demande->numero_demande} : {$motif}";

            if ($mode === 'deduction_conge') {
                $conge = TypeConge::pourEntreprise($demande->entreprise_id)->where('code', 'CA')->first()
                    ?? throw new \DomainException('Le type « Congé annuel payé » est introuvable : la déduction est impossible.');
                $solde = $this->calculateurSolde->obtenir($demande->salarie, $conge, $demande->date_debut->year);
                app(AjusterSoldeConge::class)->executer($solde, -1 * (float) $demande->duree_jours, $texte);
            } else {
                $this->constaterAbsenceNonJustifiee($demande, $texte);
            }

            $demande->update(['requalification' => $mode, 'requalifiee_le' => now()]);

            return $this->circuit->transitionner($demande->fresh(), StatutDemandeConge::REQUALIFIEE, $motif, $automatique);
        });
    }

    // Force majeure retenue par les RH : le délai de remise du justificatif est prolongé.
    public function prolongerEcheance(DemandeConge $demande, Carbon $nouvelleEcheance, string $motif): DemandeConge
    {
        if ($demande->statut !== StatutDemandeConge::ATTENTE_JUSTIFICATIF) {
            throw new \DomainException('Seule une demande en attente de justificatif peut voir son délai prolongé.');
        }
        if ($demande->echeance_justificatif && ! $nouvelleEcheance->gt($demande->echeance_justificatif)) {
            throw new \DomainException('La nouvelle échéance doit être postérieure au '.$demande->echeance_justificatif->format('d/m/Y').'.');
        }
        if (blank($motif)) {
            throw new \DomainException('Précisez le cas de force majeure.');
        }

        return DB::transaction(function () use ($demande, $nouvelleEcheance, $motif) {
            $demande->update(['echeance_justificatif' => $nouvelleEcheance]);
            HistoriqueDemande::create([
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'de_statut' => $demande->statut->value,
                'vers_statut' => $demande->statut->value,
                'acteur_id' => auth()->id(),
                'motif' => "Délai du justificatif prolongé au {$nouvelleEcheance->format('d/m/Y')} (force majeure) : {$motif}",
            ]);

            return $demande->fresh();
        });
    }

    /**
     * Requalifie les demandes dont l'échéance du justificatif est dépassée.
     *
     * @return int nombre de demandes requalifiées
     */
    public function traiterEcheances(int $entrepriseId, ?Carbon $aujourdhui = null): int
    {
        $aujourdhui = ($aujourdhui ?? today())->copy()->startOfDay();

        $demandes = DemandeConge::pourEntreprise($entrepriseId)
            ->with('typeConge')
            ->where('statut', StatutDemandeConge::ATTENTE_JUSTIFICATIF->value)
            ->whereNotNull('echeance_justificatif')
            ->where('echeance_justificatif', '<', $aujourdhui->toDateString())
            ->get();

        foreach ($demandes as $demande) {
            $this->requalifier($demande, 'justificatif non remis au '.$demande->echeance_justificatif->format('d/m/Y').'.', automatique: true);
        }

        return $demandes->count();
    }

    // Absence constatée régularisée par une demande : sa qualification suit la décision.
    public function qualifierAbsenceLiee(DemandeConge $demande): void
    {
        $demande->absence?->update([
            'qualification' => ($demande->typeConge?->estDeDroit() ? QualificationAbsence::JUSTIFIEE : QualificationAbsence::AUTORISEE)->value,
            'decision_regularisation' => "Régularisée par la demande {$demande->numero_demande}.",
        ]);
    }

    private function constaterAbsenceNonJustifiee(DemandeConge $demande, string $texte): void
    {
        if ($demande->absence) {
            $demande->absence->update([
                'qualification' => QualificationAbsence::INJUSTIFIEE->value,
                'decision_regularisation' => $texte,
            ]);

            return;
        }

        $type = TypeConge::pourEntreprise($demande->entreprise_id)->where('code', 'ABS-NJ')->first()
            ?? throw new \DomainException('Le type « Absence non justifiée » est introuvable : la requalification est impossible.');

        $absence = Absence::create([
            'entreprise_id' => $demande->entreprise_id,
            'salarie_id' => $demande->salarie_id,
            'type_conge_id' => $type->id,
            'debut_le' => $demande->date_debut->copy()->startOfDay(),
            'fin_le' => $demande->date_reprise->copy()->subDay()->endOfDay(),
            'motif' => $texte,
            'statut' => 'constatée',
            'qualification' => QualificationAbsence::INJUSTIFIEE->value,
            'decision_regularisation' => $texte,
            'statut_transmission_paie' => StatutTransmissionPaie::A_PREPARER->value,
            'cree_par' => auth()->id(),
            'etat' => 1,
        ]);

        $demande->update(['absence_id' => $absence->id]);
    }
}
