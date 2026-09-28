<?php

namespace App\Domain\Formation\Services;

use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Formation\Models\SessionFormation;

class CalculateurProgressionParticipant
{
    /**
     * Calcule la progression et les indicateurs d'un participant.
     */
    public function analyser(ParticipantFormation $participant): array
    {
        $avant = $participant->score_avant !== null ? (float) $participant->score_avant : null;
        $apres = $participant->score_apres !== null ? (float) $participant->score_apres : null;

        if ($avant === null || $apres === null) {
            return [
                'progression' => null,
                'progression_pourcent' => null,
                'evaluation' => 'Non évalué',
            ];
        }

        $progression = $apres - $avant;
        $pourcent = $avant > 0 ? round(($progression / $avant) * 100, 2) : null;

        $evaluation = match (true) {
            $progression > 20 => 'Excellente progression',
            $progression > 0  => 'Progression positive',
            $progression === 0.0 => 'Aucune progression',
            default           => 'Régression',
        };

        return [
            'progression' => round($progression, 2),
            'progression_pourcent' => $pourcent,
            'evaluation' => $evaluation,
        ];
    }

    /**
     * Analyse globale d'une session : score moyen, satisfaction moyenne, taux de présence.
     */
    public function analyserSession(SessionFormation $session): array
    {
        $participants = $session->participants()->get();

        $avecScores = $participants->filter(fn ($p) => $p->score_avant !== null && $p->score_apres !== null);

        $scoreAvantMoyen = $avecScores->avg('score_avant');
        $scoreApresMoyen = $avecScores->avg('score_apres');
        $satisfactionMoyenne = $participants
            ->filter(fn ($p) => $p->note_satisfaction !== null)
            ->avg('note_satisfaction');

        $presents = $participants->where('statut_presence', 'present')->count();

        return [
            'total_participants' => $participants->count(),
            'presents' => $presents,
            'taux_presence' => $participants->count() > 0
                ? round(($presents / $participants->count()) * 100, 2)
                : 0,
            'score_avant_moyen' => $scoreAvantMoyen !== null ? round($scoreAvantMoyen, 2) : null,
            'score_apres_moyen' => $scoreApresMoyen !== null ? round($scoreApresMoyen, 2) : null,
            'progression_moyenne' => $avecScores->count() > 0
                ? round($scoreApresMoyen - $scoreAvantMoyen, 2)
                : null,
            'satisfaction_moyenne' => $satisfactionMoyenne !== null ? round($satisfactionMoyenne, 2) : null,
        ];
    }
}