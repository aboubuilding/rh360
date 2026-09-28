<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Models\EvaluationRisque;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Services\CalculateurScoreRisque;
use App\Domain\Sst\Services\GenerateurHistoriqueSst;
use App\Domain\Sst\Enums\NaturePieceSst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluerRisque
{
    public function __construct(
        private CalculateurScoreRisque $calculateur,
        private GenerateurHistoriqueSst $historique,
    ) {}

    public function executer(Risque $risque, array $donnees): EvaluationRisque
    {
        return DB::transaction(function () use ($risque, $donnees) {
            // Calcul du score et du niveau
            $calcul = $this->calculateur->calculer(
                (int) $donnees['gravite'],
                (int) $donnees['probabilite'],
            );

            // Suggestion de la prochaine revue
            $dateRevueSuggeree = $donnees['date_prochaine_revue']
                ?? $this->calculateur->suggererProchaineRevue($calcul['niveau'])->format('Y-m-d');

            // Snapshot du contexte (figé à la date d'évaluation)
            $instantane = [
                'perimetre_revision' => $risque->revision_perimetre,
                'mesures_revision' => $risque->revision_mesures,
                'site' => $risque->site,
                'poste_id' => $risque->poste_id,
                'activite' => $risque->activite,
                'danger' => $risque->danger,
            ];

            $evaluation = EvaluationRisque::create([
                'entreprise_id' => $risque->entreprise_id,
                'risque_id' => $risque->id,
                'cle_soumission' => (string) Str::uuid(),
                'date_evaluation' => $donnees['date_evaluation'] ?? now(),
                'motif' => $donnees['motif'] ?? 'initial',
                'gravite' => $donnees['gravite'],
                'probabilite' => $donnees['probabilite'],
                'score' => $calcul['score'],
                'niveau' => $calcul['niveau']->value,
                'version_methode' => $donnees['version_methode'] ?? 'v1',
                'mesures_existantes' => $donnees['mesures_existantes'] ?? '—',
                'justification' => $donnees['justification'] ?? '—',
                'date_prochaine_revue' => $dateRevueSuggeree,
                'revision_perimetre' => $risque->revision_perimetre,
                'revision_mesures' => $risque->revision_mesures,
                'instantane_contexte' => $instantane,
                'cree_par' => auth()->id(),
                'etat' => 1,
            ]);

            // Mettre à jour la date de revue du risque
            $risque->update([
                'date_echeance_revue' => $dateRevueSuggeree,
                'revision' => $risque->revision + 1,
                'modifie_par' => auth()->id(),
            ]);

            $this->historique->enregistrer($risque, NaturePieceSst::RISQUE, $risque->revision, 'evaluated');

            return $evaluation->fresh();
        });
    }
}