<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Paie\Events\RappelGenere;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RappelAvancement;
use Illuminate\Support\Facades\DB;

class GenererRappelsAvancement
{
    /**
     * Génère les rappels pour tous les mouvements d'avancement appliqués
     * rétroactivement et non encore rappelés.
     */
    public function executer(PeriodePaie $periodeGeneration): int
    {
        return DB::transaction(function () use ($periodeGeneration) {
            // Récupérer les mouvements d'avancement effectifs avec date d'effet rétroactive
            $mouvements = MouvementCarriere::withoutGlobalScopes()
                ->where('entreprise_id', $periodeGeneration->entreprise_id)
                ->whereIn('type_mouvement', ['avancement', 'avancement_anticipe', 'promotion'])
                ->where('statut', StatutMouvement::EFFECTIF->value)
                ->whereNotNull('date_effet')
                ->whereDoesntHave('rappels')
                ->get();

            $compteur = 0;

            foreach ($mouvements as $mouvement) {
                try {
                    $rappel = $this->creerRappel($mouvement, $periodeGeneration);
                    if ($rappel) {
                        $compteur++;
                        event(new RappelGenere($rappel));
                    }
                } catch (\Throwable $e) {
                    \Log::error("Erreur génération rappel mouvement {$mouvement->id}: " . $e->getMessage());
                }
            }

            return $compteur;
        });
    }

    private function creerRappel(MouvementCarriere $mouvement, PeriodePaie $periodeGeneration): ?RappelAvancement
    {
        // Pour chaque période entre la date d'effet et la période courante,
        // calculer la différence de salaire
        $dateEffet = $mouvement->date_effet;
        if (! $dateEffet) return null;

        // Récupérer le dernier bulletin avant l'application
        $bulletinSource = BulletinPaie::where('salarie_id', $mouvement->salarie_id)
            ->whereHas('periode', function ($q) use ($dateEffet, $periodeGeneration) {
                $q->where(function ($q) use ($dateEffet, $periodeGeneration) {
                    $q->where('annee', '<', $periodeGeneration->annee)
                      ->orWhere(function ($q) use ($periodeGeneration) {
                          $q->where('annee', $periodeGeneration->annee)
                            ->where('mois', '<', $periodeGeneration->mois);
                      });
                });
            })
            ->orderByDesc('id')
            ->first();

        if (! $bulletinSource) return null;

        // Calcul simplifié : différence entre la nouvelle position et l'ancienne
        $ancienSalaire = (float) ($mouvement->positionDepart?->montant_salaire ?? 0);
        $nouveauSalaire = (float) ($mouvement->positionCible?->montant_salaire ?? 0);
        $differenceMensuelle = max(0, $nouveauSalaire - $ancienSalaire);

        // Nombre de mois entre la date d'effet et aujourd'hui
        $moisRetard = $dateEffet->diffInMonths(now());
        if ($moisRetard <= 0) return null;

        $rappelBase = round($differenceMensuelle * $moisRetard, 2);

        // Prime d'ancienneté sur le rappel (approximation : 5 %)
        $rappelAnciennete = round($rappelBase * 0.05, 2);

        return RappelAvancement::create([
            'entreprise_id' => $mouvement->entreprise_id,
            'salarie_id' => $mouvement->salarie_id,
            'mouvement_id' => $mouvement->id,
            'bulletin_source_id' => $bulletinSource->id,
            'periode_generation_id' => $periodeGeneration->id,
            'montant_rappel_base' => $rappelBase,
            'montant_rappel_anciennete' => $rappelAnciennete,
        ]);
    }
}