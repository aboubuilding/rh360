<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Services\CalculateurEligibilite;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AvancementController extends Controller
{
    public function __construct(private CalculateurEligibilite $calculateur) {}

    public function index(Request $request)
    {
        $this->authorize('permission', 'carriere.view');

        $jours = (int) $request->get('jours', 90);
        $entrepriseId = auth()->user()->entreprise_id;

        $echeances = $this->calculateur->echeancesProches($entrepriseId, $jours);
        $sansDate = $this->calculateur->sansDateCalculable($entrepriseId);

        return view('carriere.avancements.index', [
            'echeances' => $echeances,
            'sansDate' => $sansDate,
            'jours' => $jours,
        ]);
    }

    public function preparerPropositions(Request $request)
    {
        $this->authorize('permission', 'carriere.manage');

        $jours = (int) $request->get('jours', 90);
        $entrepriseId = auth()->user()->entreprise_id;

        $echeances = $this->calculateur->echeancesProches($entrepriseId, $jours);
        $crees = 0;

        foreach ($echeances as $calcul) {
            // Ne créer la proposition que si le salarié n'a pas déjà un
            // mouvement en cours pour un avancement
            $existe = \App\Domain\Carriere\Models\MouvementCarriere::where('salarie_id', $calcul['salarie_id'])
                ->whereIn('type_mouvement', ['avancement', 'avancement_anticipe'])
                ->whereIn('statut', ['draft', 'proposed', 'to_check', 'checked', 'validated', 'scheduled'])
                ->exists();

            if ($existe) continue;

            try {
                app(\App\Domain\Carriere\Actions\CreerMouvement::class)->executer([
                    'salarie_id' => $calcul['salarie_id'],
                    'type_mouvement' => 'avancement',
                    'date_eligibilite' => $calcul['date_eligibilite'],
                    'position_classification_cible_id' => $calcul['position_suivante_id'],
                    'motif' => 'Proposition automatique : échéance ' . $calcul['date_eligibilite'],
                ]);
                $crees++;
            } catch (\Throwable $e) {
                \Log::warning("Impossible de créer la proposition d'avancement pour salarié {$calcul['salarie_id']} : " . $e->getMessage());
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$crees} proposition(s) d'avancement créée(s)."]);
        }
        return back()->with('success', "{$crees} proposition(s) créée(s).");
    }
}