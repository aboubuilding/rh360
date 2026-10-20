<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Requests\UpdateParametresInterimRequest;
use App\Domain\Interim\Services\CatalogueParametresInterim;
use App\Domain\Interim\Services\ParametresInterim;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Paramètres de la gestion des intérims de l'entreprise. Par défaut, les valeurs
 * prévues par les textes (config/interim.php).
 */
class ParametreInterimController extends Controller
{
    public function __construct(private ParametresInterim $parametres) {}

    public function edit()
    {
        $this->authorize('create', InterimInterne::class);

        $lignes = collect(CatalogueParametresInterim::definitions())
            ->map(fn ($definition, $cle) => $definition + [
                'cle' => $cle,
                'valeur' => $this->parametres->get($cle),
                'defaut' => $this->parametres->defaut($cle),
                'modifie' => $this->parametres->estSurcharge($cle),
            ])
            ->groupBy('groupe');

        return view('carriere.interims.parametres', [
            'groupes' => $lignes,
            'referentiels' => ReferentielClassification::orderBy('nom')->get(),
            'typesConges' => TypeConge::orderBy('nom')->get(),
        ]);
    }

    public function update(UpdateParametresInterimRequest $request)
    {
        $this->authorize('create', InterimInterne::class);

        // Une liste vide n'est pas transmise par le navigateur : on la rétablit explicitement.
        $valeurs = $request->validated() + ['referentiels_conventions_ids' => [], 'codes_conges_maladie' => [], 'codes_absences_greve' => []];
        $this->parametres->enregistrer($valeurs);

        return $this->repondre($request, 'Paramètres enregistrés.');
    }

    public function reinitialiser(Request $request)
    {
        $this->authorize('create', InterimInterne::class);

        $this->parametres->reinitialiser();

        return $this->repondre($request, 'Valeurs par défaut rétablies.');
    }

    private function repondre(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('carriere.interims.parametres')->with('success', $message);
    }
}
