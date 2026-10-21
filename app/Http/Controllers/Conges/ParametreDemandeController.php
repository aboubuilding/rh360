<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Requests\UpdateParametresDemandesRequest;
use App\Domain\Conges\Services\ParametresDemandes;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Paramètres des demandes et autorisations de l'entreprise.
 */
class ParametreDemandeController extends Controller
{
    public function __construct(private ParametresDemandes $parametres) {}

    public function edit()
    {
        $this->authorize('permission', 'conges.manage');

        $groupes = collect($this->parametres->definitions())
            ->map(fn ($definition, $cle) => $definition + [
                'cle' => $cle,
                'valeur' => $this->parametres->get($cle),
                'defaut' => $this->parametres->defaut($cle),
                'modifie' => $this->parametres->estSurcharge($cle),
            ])
            ->groupBy('groupe');

        return view('conges.parametres', [
            'groupes' => $groupes,
            'referentiels' => collect(),
            'typesConges' => collect(),
        ]);
    }

    public function update(UpdateParametresDemandesRequest $request)
    {
        $this->authorize('permission', 'conges.manage');

        $this->parametres->enregistrer($request->validated());

        return $this->repondre($request, 'Paramètres enregistrés.');
    }

    public function reinitialiser(Request $request)
    {
        $this->authorize('permission', 'conges.manage');

        $this->parametres->reinitialiser();

        return $this->repondre($request, 'Valeurs par défaut rétablies.');
    }

    private function repondre(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('conges.parametres')->with('success', $message);
    }
}
