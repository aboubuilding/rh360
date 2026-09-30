<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Models\RegleEvolution;
use App\Domain\Classification\Requests\StoreRegleEvolutionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegleEvolutionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RegleEvolution::class);

        $regles = RegleEvolution::query()
            ->with('referentiel')
            ->when($request->filled('referentiel_id'), fn ($q) => $q->where('referentiel_id', $request->referentiel_id))
            ->when($request->filled('type_evolution'), fn ($q) => $q->where('type_evolution', $request->type_evolution))
            ->orderBy('priorite')
            ->paginate(50)
            ->withQueryString();

        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.regles-evolution.index', compact('regles', 'referentiels'));
    }

    public function create()
    {
        $this->authorize('create', RegleEvolution::class);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.regles-evolution.create', compact('referentiels'));
    }

    public function store(StoreRegleEvolutionRequest $request)
    {
        $this->authorize('create', RegleEvolution::class);
        RegleEvolution::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle créée.']);
        }

        return redirect()->route('classification.regles-evolution.index')
            ->with('success', 'Règle d\'évolution créée.');
    }

    public function edit(RegleEvolution $regleEvolution)
    {
        $this->authorize('update', $regleEvolution);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.regles-evolution.edit', compact('regleEvolution', 'referentiels'));
    }

    public function update(StoreRegleEvolutionRequest $request, RegleEvolution $regleEvolution)
    {
        $this->authorize('update', $regleEvolution);
        $regleEvolution->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }

        return redirect()->route('classification.regles-evolution.index')
            ->with('success', 'Règle mise à jour.');
    }

    public function destroy(RegleEvolution $regleEvolution)
    {
        $this->authorize('delete', $regleEvolution);
        $regleEvolution->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Règle supprimée.']);
        }

        return redirect()->route('classification.regles-evolution.index')
            ->with('success', 'Règle supprimée.');
    }
}