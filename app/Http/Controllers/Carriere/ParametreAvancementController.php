<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Requests\UpdateParametresAvancementRequest;
use App\Domain\Carriere\Services\ParametresAvancement;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Models\TypeConge;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Paramètres des avancements et de l'ancienneté de l'entreprise.
 */
class ParametreAvancementController extends Controller
{
    public function __construct(private ParametresAvancement $parametres) {}

    public function edit()
    {
        $this->authorize('permission', 'carriere.manage');

        $groupes = collect($this->parametres->definitions())
            ->map(fn ($definition, $cle) => $definition + [
                'cle' => $cle,
                'valeur' => $this->parametres->get($cle),
                'defaut' => $this->parametres->defaut($cle),
                'modifie' => $this->parametres->estSurcharge($cle),
            ])
            ->groupBy('groupe');

        return view('carriere.avancements.parametres', [
            'groupes' => $groupes,
            'referentiels' => ReferentielClassification::orderBy('nom')->get(),
            'typesConges' => TypeConge::orderBy('nom')->get(),
        ]);
    }

    public function update(UpdateParametresAvancementRequest $request)
    {
        $this->authorize('permission', 'carriere.manage');

        // Une liste vide n'est pas transmise par le navigateur : on la rétablit explicitement.
        $this->parametres->enregistrer($request->validated() + ['codes_maladie_anciennete' => [], 'codes_absences_personnelles' => []]);

        return $this->repondre($request, 'Paramètres enregistrés.');
    }

    public function reinitialiser(Request $request)
    {
        $this->authorize('permission', 'carriere.manage');

        $this->parametres->reinitialiser();

        return $this->repondre($request, 'Valeurs par défaut rétablies.');
    }

    private function repondre(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('carriere.avancements.parametres')->with('success', $message);
    }
}
