<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\ClasseClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Requests\StoreClasseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ReferentielClassification::class);

        $classes = ClasseClassification::query()
            ->with('referentiel')
            ->when($request->filled('referentiel_id'), fn ($q) => $q->where('referentiel_id', $request->referentiel_id))
            ->orderBy('ordre')
            ->paginate(50)
            ->withQueryString();

        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.classes.index', compact('classes', 'referentiels'));
    }

    public function store(StoreClasseRequest $request)
    {
        $this->authorize('create', ReferentielClassification::class);
        ClasseClassification::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Classe créée.']);
        }

        return redirect()->route('classification.classes.index')
            ->with('success', 'Classe créée.');
    }

    public function update(StoreClasseRequest $request, ClasseClassification $classe)
    {
        $this->authorize('update', ReferentielClassification::class);
        $classe->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Classe mise à jour.']);
        }

        return redirect()->route('classification.classes.index')
            ->with('success', 'Classe mise à jour.');
    }

    public function destroy(ClasseClassification $classe)
    {
        $this->authorize('delete', ReferentielClassification::class);

        if ($classe->positions()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des positions utilisent cette classe.');
        }

        $classe->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Classe supprimée.']);
        }

        return redirect()->route('classification.classes.index')
            ->with('success', 'Classe supprimée.');
    }
}