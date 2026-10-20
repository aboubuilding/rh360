<?php

namespace App\Domain\Interim\Services;

use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\StatutPret;
use App\Domain\Interim\Models\AlerteInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Models\MissionInterim;
use App\Domain\Interim\Models\PretMainOeuvre;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Tâche quotidienne des intérims d'une entreprise : alertes d'échéance, passage au 3e mois,
 * passage « à régulariser » à la date limite, alerte maladie du titulaire à 6 mois.
 * Idempotente : une alerte est unique par objet et par clé.
 */
class BalayeurInterims
{
    public function __construct(
        private ParametresInterim $parametres,
        private CalculateurDelaisInterim $delais,
        private IndemniteInterimCalculator $indemnite,
        private InterimInterneService $service,
        private PreparateurPaieInterims $paie,
    ) {}

    /**
     * @return array{alertes:int, a_regulariser:int}
     */
    public function balayer(int $entrepriseId): array
    {
        $this->parametres->pourEntreprise($entrepriseId);
        $aujourdhui = today();
        $bilan = ['alertes' => 0, 'a_regulariser' => 0];

        $interims = InterimInterne::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('etat', 1)
            ->with(['salarie', 'posteOccupe'])
            ->get();

        foreach ($interims as $interim) {
            if ($interim->estClos()) {
                $this->fermerToutes($interim);

                continue;
            }

            $actives = $this->alertesInterim($interim, $aujourdhui, $bilan);
            $this->fermerSauf($interim, $actives);
        }

        $bilan['alertes'] += $this->alertesMaladieTitulaire($entrepriseId, $aujourdhui);
        $this->alertesSstMissions($entrepriseId, $aujourdhui, $bilan);
        $this->alertesPrets($entrepriseId, $aujourdhui, $bilan);

        return $bilan;
    }

    /**
     * RG-PMO-02 — pièces obligatoires à réunir avant le début ; RG-PMO-05 — retour du salarié à acter.
     *
     * @param  array{alertes:int, a_regulariser:int}  $bilan
     */
    private function alertesPrets(int $entrepriseId, Carbon $aujourdhui, array &$bilan): void
    {
        $joursAvant = (int) $this->parametres->get('alerte_pieces_pret_jours');

        $prets = PretMainOeuvre::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('etat', 1)
            ->with('salarie')
            ->get();

        foreach ($prets as $pret) {
            $actives = [];

            if ($pret->statut === StatutPret::PROJET && $aujourdhui->gte($pret->date_debut->copy()->subDays($joursAvant))) {
                $manquantes = $pret->piecesManquantes();
                if ($manquantes !== []) {
                    $depasse = $aujourdhui->gte($pret->date_debut);
                    $actives[] = $this->alerter($pret, 'pieces_pret', $depasse ? 'danger' : 'warning',
                        "Pièces manquantes — prêt {$pret->reference}",
                        "{$pret->salarie?->nom_complet}, début le {$pret->date_debut->format('d/m/Y')} : "
                        .implode(' ; ', array_map(fn ($t) => $t->libelle(), $manquantes))
                        .'. Le prêt ne peut pas commencer sans ces trois pièces (Code du travail, art. 8).',
                        $pret->date_debut, $bilan);
                }
            }

            if ($pret->statut === StatutPret::EN_COURS && $aujourdhui->gt($pret->date_fin)) {
                $actives[] = $this->alerter($pret, 'retour_pret', 'warning',
                    "Retour à enregistrer — prêt {$pret->reference}",
                    "{$pret->salarie?->nom_complet} : le prêt a pris fin le {$pret->date_fin->format('d/m/Y')}. Le salarié retrouve son "
                    .'poste ou un poste équivalent, sans baisse de catégorie ni de salaire (Code du travail, art. 8).',
                    $pret->date_fin, $bilan);
            }

            AlerteInterim::withoutGlobalScopes()->where('objet_type', $pret->getMorphClass())->where('objet_id', $pret->id)
                ->where('en_cours', true)->whereNotIn('cle', $actives)
                ->update(['en_cours' => false, 'cloture_le' => now()]);
        }
    }

    /**
     * RG-TT-07 — Checklist SST à valider avant le premier jour de mission.
     *
     * @param  array{alertes:int, a_regulariser:int}  $bilan
     */
    private function alertesSstMissions(int $entrepriseId, Carbon $aujourdhui, array &$bilan): void
    {
        $joursAvant = (int) $this->parametres->get('alerte_sst_jours_avant_mission');

        $missions = MissionInterim::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->where('etat', 1)
            ->with('poste')
            ->get();

        foreach ($missions as $mission) {
            $concernee = ! $mission->statut->estFinal()
                && ! $mission->checklistSstComplete()
                && $aujourdhui->gte($mission->date_debut->copy()->subDays($joursAvant));

            if (! $concernee) {
                AlerteInterim::withoutGlobalScopes()->where('objet_type', $mission->getMorphClass())
                    ->where('objet_id', $mission->id)->where('cle', 'checklist_sst')->where('en_cours', true)
                    ->update(['en_cours' => false, 'cloture_le' => now()]);

                continue;
            }

            $commencee = $aujourdhui->gte($mission->date_debut);
            $this->alerter($mission, 'checklist_sst', $commencee ? 'danger' : 'warning',
                ($commencee ? 'Mission commencée sans checklist SST — ' : 'Checklist SST à valider — ').$mission->reference,
                "{$mission->nom_interimaire} ({$mission->poste?->intitule}), début le {$mission->date_debut->format('d/m/Y')} : "
                .'accueil sécurité, EPI remis et consignes du poste à valider avant le premier jour ; l\'entreprise '
                .'utilisatrice répond de la santé, de la sécurité et de l\'hygiène (Code du travail, art. 60 à 63).',
                $mission->date_debut, $bilan);
        }
    }

    /**
     * @param  array{alertes:int, a_regulariser:int}  $bilan
     * @return list<string> clés des alertes actives
     */
    private function alertesInterim(InterimInterne $interim, Carbon $aujourdhui, array &$bilan): array
    {
        $actives = [];
        $joursAvant = (int) $this->parametres->get('alerte_jours_avant_fin');
        $qui = $interim->salarie?->nom_complet.' — '.$interim->posteOccupe?->intitule;

        $dejaEffectues = $this->delais->joursDejaEffectues($interim->poste_occupe_id, $interim->titulaire_id, $interim->date_debut, $interim->id);
        $finDeuxMois = $this->delais->plafondNormal($interim->date_debut, $dejaEffectues);
        $dureeMax = $this->parametres->get('duree_max_interim_mois');

        // RG-INT-03 — J-15 avant la fin des deux mois.
        if ($aujourdhui->betweenIncluded($finDeuxMois->copy()->subDays($joursAvant), $finDeuxMois)
            && $interim->date_fin_prevue->gte($finDeuxMois)) {
            $suite = $interim->motif->estEligibleProlongation()
                ? 'prolonger (motif éligible) ou décider d\'un reclassement ou d\'un retour'
                : 'le motif ne permet pas de prolongation : décider d\'un reclassement ou d\'un retour';
            $actives[] = $this->alerter($interim, 'echeance_duree_max', 'warning',
                "Fin des {$dureeMax} mois d'intérim le {$finDeuxMois->format('d/m/Y')}",
                "{$qui} : {$suite} (CCIT, art. 13).", $finDeuxMois, $bilan);
        }

        // RG-INT-05 — J-15 avant la date limite du délai légal, si elle est postérieure.
        if ($interim->date_limite->gt($finDeuxMois)
            && $aujourdhui->betweenIncluded($interim->date_limite->copy()->subDays($joursAvant), $interim->date_limite)) {
            $actives[] = $this->alerter($interim, 'echeance_delai_legal', 'warning',
                "Fin du délai légal de l'intérim le {$interim->date_limite->format('d/m/Y')}",
                "{$qui} : une décision de reclassement ou de retour sera obligatoire ({$interim->motif->referenceDelaiLegal()}).",
                $interim->date_limite, $bilan);
        }

        // RG-INT-06 — Passage au 3e mois : l'indemnité d'intérim est due.
        $debutIndemnite = $this->indemnite->debutIndemnite($interim);
        if ($aujourdhui->gte($debutIndemnite) && $interim->date_fin_prevue->gte($debutIndemnite)) {
            $this->paie->rubriqueIndemnite($interim->entreprise_id);
            $actives[] = $this->alerter($interim, 'debut_indemnite', 'info',
                "Indemnité d'intérim due depuis le {$debutIndemnite->format('d/m/Y')}",
                "{$qui} : à partir du 3e mois, différence entre le minimum de la catégorie occupée et le salaire de base, "
                .'versée par la paie (CCIT, art. 13).', $debutIndemnite, $bilan);
        }

        // RG-INT-09 — Délai dépassé sans décision : à régulariser (jamais clôturé automatiquement).
        if ($aujourdhui->gt($interim->date_limite)) {
            if ($interim->statut !== StatutInterim::A_REGULARISER) {
                $this->service->passerARegulariser($interim,
                    "Délai expiré le {$interim->date_limite->format('d/m/Y')} sans décision de reclassement ou de retour.");
                $bilan['a_regulariser']++;
            }
            $actives[] = $this->alerter($interim, 'a_regulariser', 'danger',
                'Intérim à régulariser',
                "{$qui} : délai expiré le {$interim->date_limite->format('d/m/Y')} sans décision. "
                .'Un reclassement ou un retour est obligatoire ; la validation de la paie est bloquée (CCIT, art. 13).',
                $interim->date_limite, $bilan);
        } elseif ($aujourdhui->gt($interim->date_fin_prevue)) {
            // Fin prévue dépassée sans clôture, dans la limite du délai.
            $actives[] = $this->alerter($interim, 'fin_prevue_depassee', 'warning',
                "Fin prévue dépassée ({$interim->date_fin_prevue->format('d/m/Y')})",
                "{$qui} : clôturer l'intérim, le prolonger ou décider d'un reclassement ou d'un retour.",
                $interim->date_fin_prevue, $bilan);
        }

        return $actives;
    }

    /**
     * RG-REM-06 — Absence maladie d'au moins 6 mois : le remplacement définitif devient possible,
     * après avis du médecin-inspecteur du travail.
     */
    private function alertesMaladieTitulaire(int $entrepriseId, Carbon $aujourdhui): int
    {
        $mois = (int) $this->parametres->get('alerte_maladie_titulaire_mois');
        $codes = (array) $this->parametres->get('codes_conges_maladie');
        $creees = 0;

        $absences = Absence::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->whereHas('typeConge', fn ($q) => $q->withoutGlobalScopes()->whereIn('code', $codes))
            ->with('salarie')
            ->get();

        foreach ($absences as $absence) {
            $seuil = $this->delais->ajouterMois($absence->debut_le, $mois);
            $enCours = ! $absence->fin_le || Carbon::parse($absence->fin_le)->gte($aujourdhui);

            if ($enCours && $aujourdhui->gte($seuil)) {
                $bilan = ['alertes' => 0];
                $this->alerter($absence, 'maladie_titulaire', 'warning',
                    "Absence maladie de {$mois} mois — {$absence->salarie?->nom_complet}",
                    "Absent pour maladie depuis le {$absence->debut_le->format('d/m/Y')} : le remplacement définitif "
                    .'devient possible, après avis du médecin-inspecteur du travail (Code du travail, art. 65 c ; CCIT, art. 14 et 16).',
                    $seuil, $bilan);
                $creees += $bilan['alertes'];
            } else {
                AlerteInterim::withoutGlobalScopes()->where('objet_type', $absence->getMorphClass())
                    ->where('objet_id', $absence->id)->where('cle', 'maladie_titulaire')->where('en_cours', true)
                    ->update(['en_cours' => false, 'cloture_le' => now()]);
            }
        }

        return $creees;
    }

    /** @param array<string, int> $bilan */
    private function alerter(Model $objet, string $cle, string $niveau, string $intitule, string $message, ?Carbon $echeance, array &$bilan): string
    {
        $alerte = AlerteInterim::withoutGlobalScopes()->firstOrNew([
            'objet_type' => $objet->getMorphClass(),
            'objet_id' => $objet->getKey(),
            'cle' => $cle,
        ]);

        if (! $alerte->exists || ! $alerte->en_cours) {
            $bilan['alertes']++;
        }

        $alerte->fill([
            'entreprise_id' => $objet->entreprise_id,
            'niveau' => $niveau,
            'intitule' => $intitule,
            'message' => $message,
            'date_echeance' => $echeance,
            'en_cours' => true,
            'cloture_le' => null,
        ])->save();

        return $cle;
    }

    /** @param list<string> $actives */
    private function fermerSauf(InterimInterne $interim, array $actives): void
    {
        AlerteInterim::withoutGlobalScopes()
            ->where('objet_type', $interim->getMorphClass())
            ->where('objet_id', $interim->id)
            ->where('en_cours', true)
            ->whereNotIn('cle', $actives)
            ->update(['en_cours' => false, 'cloture_le' => now()]);
    }

    private function fermerToutes(InterimInterne $interim): void
    {
        $this->fermerSauf($interim, []);
    }
}
