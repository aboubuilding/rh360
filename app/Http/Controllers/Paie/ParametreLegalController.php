<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RegleCotisation;
use App\Domain\Paie\Models\RegleIrpp;
use App\Domain\Paie\Requests\StoreRegleAncienneteRequest;
use App\Domain\Paie\Requests\StoreRegleCotisationRequest;
use App\Domain\Paie\Requests\StoreRegleIrppRequest;
use App\Http\Controllers\Controller;

class ParametreLegalController extends Controller
{
    public function index()
    {
        $this->authorize('permission', 'paie.manage');

        $cotisations = RegleCotisation::orderByDesc('debut_effet')->get();
        $anciennete = RegleAnciennete::orderByDesc('debut_effet')->get();
        $irpp = RegleIrpp::orderByDesc('debut_effet')->get();

        return view('paie.parametres.index', compact('cotisations', 'anciennete', 'irpp'));
    }

    // --- Cotisations ---

    public function storeCotisation(StoreRegleCotisationRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleCotisation::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle de cotisation créée.']);
        }
        return back()->with('success', 'Règle de cotisation créée.');
    }

    public function updateCotisation(StoreRegleCotisationRequest $request, RegleCotisation $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }
        return back()->with('success', 'Règle mise à jour.');
    }

    public function destroyCotisation(RegleCotisation $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Règle supprimée.']);
        }
        return back()->with('success', 'Règle supprimée.');
    }

    // --- Ancienneté ---

    public function storeAnciennete(StoreRegleAncienneteRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleAnciennete::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle d\'ancienneté créée.']);
        }
        return back()->with('success', 'Règle d\'ancienneté créée.');
    }

    public function updateAnciennete(StoreRegleAncienneteRequest $request, RegleAnciennete $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }
        return back()->with('success', 'Règle mise à jour.');
    }

    // --- IRPP ---

    public function storeIrpp(StoreRegleIrppRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleIrpp::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Barème IRPP créé.']);
        }
        return back()->with('success', 'Barème IRPP créé.');
    }

    public function updateIrpp(StoreRegleIrppRequest $request, RegleIrpp $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Barème mis à jour.']);
        }
        return back()->with('success', 'Barème mis à jour.');
    }
}