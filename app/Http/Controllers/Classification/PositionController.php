<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\ClasseClassification;
use App\Domain\Classification\Models\EchelonClassification;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Requests\StorePositionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PositionClassification::class);

        $positions = PositionClassification::query()
            ->with(['referentiel', 'categorie', 'classe', 'echelon'])
            ->when($request->filled('referentiel_id'), fn ($q) => $q->where('referentiel_id', $request->referentiel_id))
            ->when($request->filled('categorie_id'), fn ($q) => $q->where('categorie_id', $request->categorie_id))
            ->orderBy('ordre')
            ->paginate(50)
            ->withQueryString();

        $referentiels = ReferentielClassification::orderBy('nom')->get();
        $categories = CategorieClassification::orderBy('ordre')->get();

        return view('classification.positions.index', compact('positions', 'referentiels', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', PositionClassification::class);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        $categories = CategorieClassification::orderBy('ordre')->get();
        $classes = ClasseClassification::orderBy('ordre')->get();
        $echelons = EchelonClassification::orderBy('ordre')->get();
        $positions = PositionClassification::orderBy('ordre')->get();
        return view('classification.positions.create', compact('referentiels', 'categories', 'classes', 'echelons', 'positions'));
    }

    public function store(StorePositionRequest $request)
    {
        $this->authorize('create', PositionClassification::class);
        PositionClassification::create($request->validated());
        return redirect()->route('classification.positions.index')
            ->with('success', 'Position créée.');
    }

    public function edit(PositionClassification $position)
    {
        $this->authorize('update', $position);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        $categories = CategorieClassification::orderBy('ordre')->get();
        $classes = ClasseClassification::orderBy('ordre')->get();
        $echelons = EchelonClassification::orderBy('ordre')->get();
        $positions = PositionClassification::where('id', '!=', $position->id)->orderBy('ordre')->get();
        return view('classification.positions.edit', compact('position', 'referentiels', 'categories', 'classes', 'echelons', 'positions'));
    }

    public function update(StorePositionRequest $request, PositionClassification $position)
    {
        $this->authorize('update', $position);
        $position->update($request->validated());
        return redirect()->route('classification.positions.index')
            ->with('success', 'Position mise à jour.');
    }

    public function destroy(PositionClassification $position)
    {
        $this->authorize('delete', $position);
        $position->marquerSupprime();
        return redirect()->route('classification.positions.index')
            ->with('success', 'Position supprimée.');
    }
}