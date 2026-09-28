<?php

namespace App\Domain\Performance\Services;

use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;

class GenerateurStatistiquesPerformance
{
    /**
     * Statistiques d'une campagne d'évaluation.
     */
    public function campagne(CampagneEvaluation $campagne): array
    {
        $entretiens = $campagne->entretiens()->get();

        $valides = $entretiens->where('statut', 'valide');
        $notesFinales = $valides->pluck('note_finale')->filter()->all();

        $moyenne = count($notesFinales) > 0
            ? round(array_sum($notesFinales) / count($notesFinales), 2)
            : null;

        // Répartition par appréciation
        $appreciations = ['Excellent' => 0, 'Très bien' => 0, 'Bien' => 0, 'Satisfaisant' => 0, 'Insuffisant' => 0, 'Très insuffisant' => 0];
        $calculateur = app(CalculateurNoteFinale::class);

        foreach ($valides as $e) {
            $app = $calculateur->appreciation($e->note_finale);
            if (isset($appreciations[$app])) {
                $appreciations[$app]++;
            }
        }

        return [
            'total_entretiens' => $entretiens->count(),
            'valides' => $valides->count(),
            'en_cours' => $entretiens->whereIn('statut', ['a_preparer', 'auto_evalue', 'realise'])->count(),
            'taux_realisation' => $entretiens->count() > 0
                ? round(($valides->count() / $entretiens->count()) * 100, 2)
                : 0,
            'note_moyenne' => $moyenne,
            'appreciations' => $appreciations,
        ];
    }

    /**
     * Statistiques d'un salarié sur toutes ses campagnes.
     */
    public function salarie(int $salarieId): array
    {
        $entretiens = EntretienEvaluation::where('salarie_id', $salarieId)
            ->where('statut', 'valide')
            ->orderByDesc('date_entretien')
            ->get();

        $notes = $entretiens->pluck('note_finale')->filter()->all();

        return [
            'nombre_evaluations' => $entretiens->count(),
            'derniere_note' => $entretiens->first()?->note_finale,
            'derniere_evaluation' => $entretiens->first()?->date_entretien?->format('d/m/Y'),
            'note_moyenne' => count($notes) > 0 ? round(array_sum($notes) / count($notes), 2) : null,
            'evolution' => $this->calculerEvolution($entretiens),
        ];
    }

    private function calculerEvolution($entretiens): ?float
    {
        if ($entretiens->count() < 2) return null;

        $derniere = (float) $entretiens->first()->note_finale;
        $precedente = (float) $entretiens->skip(1)->first()->note_finale;

        return round($derniere - $precedente, 2);
    }
}