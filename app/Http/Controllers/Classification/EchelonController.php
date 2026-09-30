<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Requests\StoreEchelonRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EchelonController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ReferentielClassification::class);

        $echelons = EchelonClassification::query()
            ->with('referentiel')
            ->when($request->filled('referentiel_id'), fn ($q) => $q->where('referentiel_id', $request->referentiel_id))
            ->orderBy('ordre')
            ->paginate(50)
            ->withQueryString();

        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.echelons.index', compact('echelons', 'referentiels'));
    }

    public function store(StoreEchelonRequest $request)
    {
        $this->authorize('create', ReferentielClassification::class);
        EchelonClassification::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Échelon créé.']);
        }

        return redirect()->route('classification.echelons.index')
            ->with('success', 'Échelon créé.');
    }

    public function update(StoreEchelonRequest $request, EchelonClassification $echelon)
    {
        $this->authorize('update', ReferentielClassification::class);
        $echelon->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Échelon mis à jour.']);
        }

        return redirect()->route('classification.echelons.index')
            ->with('success', 'Échelon mis à jour.');
    }

    public function destroy(EchelonClassification $echelon)
    {
        $this->authorize('delete', ReferentielClassification::class);

        if ($echelon->positions()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des positions utilisent cet échelon.');
        }

        $echelon->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Échelon supprimé.']);
        }

        return redirect()->route('classification.echelons.index')
            ->with('success', 'Échelon supprimé.');
    }
}