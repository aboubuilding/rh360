<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategorieController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ReferentielClassification::class);

        $categories = CategorieClassification::query()
            ->with('referentiel')
            ->when($request->filled('referentiel_id'), fn ($q) => $q->where('referentiel_id', $request->referentiel_id))
            ->orderBy('ordre')
            ->paginate(50)
            ->withQueryString();

        $referentiels = ReferentielClassification::orderBy('nom')->get();

        return view('classification.categories.index', compact('categories', 'referentiels'));
    }

    public function create()
    {
        $this->authorize('create', ReferentielClassification::class);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.categories.create', compact('referentiels'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', ReferentielClassification::class);

        $donnees = $request->validate([
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('categories_classification')
                    ->where('referentiel_id', $request->referentiel_id),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ]);

        CategorieClassification::create($donnees);

        return redirect()->route('classification.categories.index')
            ->with('success', 'Catégorie créée.');
    }

    public function edit(CategorieClassification $category)
    {
        $this->authorize('update', ReferentielClassification::class);
        $referentiels = ReferentielClassification::orderBy('nom')->get();
        return view('classification.categories.edit', compact('category', 'referentiels'));
    }

    public function update(Request $request, CategorieClassification $category)
    {
        $this->authorize('update', ReferentielClassification::class);

        $donnees = $request->validate([
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('categories_classification')
                    ->where('referentiel_id', $request->referentiel_id)
                    ->ignore($category->id),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ]);

        $category->update($donnees);

        return redirect()->route('classification.categories.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(CategorieClassification $category)
    {
        $this->authorize('delete', ReferentielClassification::class);

        if ($category->positions()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des positions utilisent cette catégorie.');
        }

        $category->marquerSupprime();

        return redirect()->route('classification.categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}