<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Enums\BaseCalculEcheance;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Models\AlerteContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\ParametreContrat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateurAlertesContrats
{
    public function genererPourContrat(Contrat $contrat): void
    {
        if (in_array($contrat->statut, [StatutContrat::ANNULE], true)) {
            return;
        }

        $parametres = ParametreContrat::find($contrat->entreprise_id);
        $seuils = $parametres?->seuils ?? [30, 15, 7, 0];

        // 1. Alerte de fin de contrat (si date de fin)
        if ($contrat->date_fin) {
            $this->creerAlerte(
                contrat: $contrat,
                cle: 'fin_contrat',
                intitule: 'Fin de contrat',
                dateCible: $contrat->date_fin,
                baseCalcul: BaseCalculEcheance::DATE_FIN,
                seuils: $seuils,
            );
        }

        // 2. Alerte de fin d'essai
        $calculateur = app(CalculateurFinEssai::class);
        $calcul = $calculateur->calculer($contrat);
        if ($calcul['date_fin_ajustee']) {
            $this->creerAlerte(
                contrat: $contrat,
                cle: 'fin_essai',
                intitule: 'Fin de période d\'essai',
                dateCible: Carbon::parse($calcul['date_fin_ajustee']),
                baseCalcul: BaseCalculEcheance::DATE_ESSAI_FIN,
                seuils: $seuils,
            );
        }

        // 3. Alerte de signature attendue (si validé mais non signé)
        if ($contrat->statut === StatutContrat::VALIDE) {
            AlerteContrat::firstOrCreate(
                ['contrat_id' => $contrat->id, 'cle' => 'signature_attendue'],
                [
                    'entreprise_id' => $contrat->entreprise_id,
                    'intitule' => 'Signature attendue',
                    'date_echeance' => now()->addDays(7),
                    'base_calcul' => BaseCalculEcheance::DATE_SIGNATURE->value,
                    'en_cours' => true,
                    'etat' => 1,
                ]
            );
        }
    }

    /**
     * Resynchronise toutes les alertes en cours pour un contrat :
     * supprime celles qui ne sont plus pertinentes, recrée les autres.
     */
    public function resynchroniser(Contrat $contrat): void
    {
        DB::transaction(function () use ($contrat) {
            // Clôturer les alertes obsolètes
            $contrat->alertes()->where('en_cours', true)->update([
                'en_cours' => false,
                'cloture_le' => now(),
                'note_cloture' => 'Resynchronisation automatique',
            ]);

            // Recréer
            $this->genererPourContrat($contrat);
        });
    }

    /**
     * Balayage global : à exécuter quotidiennement par le Scheduler.
     */
    public function balayer(int $entrepriseId): int
    {
        $contrats = Contrat::withoutGlobalScopes()
            ->where('entreprise_id', $entrepriseId)
            ->whereIn('statut', [StatutContrat::VALIDE->value, StatutContrat::SIGNE->value])
            ->where(function ($q) {
                $q->whereNotNull('date_fin')
                  ->orWhere('date_debut', '>=', now()->subMonths(6));
            })
            ->get();

        $compteur = 0;
        foreach ($contrats as $contrat) {
            $this->genererPourContrat($contrat);
            $compteur++;
        }

        return $compteur;
    }

    private function creerAlerte(
        Contrat $contrat,
        string $cle,
        string $intitule,
        Carbon $dateCible,
        BaseCalculEcheance $baseCalcul,
        array $seuils,
    ): void {
        AlerteContrat::updateOrCreate(
            ['contrat_id' => $contrat->id, 'cle' => $cle],
            [
                'entreprise_id' => $contrat->entreprise_id,
                'intitule' => $intitule,
                'date_echeance' => $dateCible,
                'base_calcul' => $baseCalcul->value,
                'en_cours' => $dateCible->isFuture(),
                'etat' => 1,
            ]
        );
    }
}