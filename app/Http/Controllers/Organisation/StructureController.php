<?php

namespace App\Http\Controllers\Organisation;

use App\Domain\Organisation\Actions\FusionnerStructures;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Models\TypeStructure;
use App\Domain\Organisation\Requests\StoreStructureRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Structure::class);

        $structures = Structure::query()
            ->with(['typeStructure', 'parent'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->q}%")
                  ->orWhere('code', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('type_structure_id'), fn ($q) => $q->where('type_structure_id', $request->type_structure_id))
            ->orderBy('nom')
            ->paginate(50)
            ->withQueryString();

        $types = TypeStructure::orderBy('ordre')->get();

        return view('organisation.structures.index', compact('structures', 'types'));
    }

    public function create()
    {
        $this->authorize('create', Structure::class);
        $types = TypeStructure::orderBy('ordre')->get();
        $parents = Structure::orderBy('nom')->get();
        return view('organisation.structures.create', compact('types', 'parents'));
    }

    public function store(StoreStructureRequest $request)
    {
        $this->authorize('create', Structure::class);
        Structure::create($request->validated());
        return redirect()->route('organisation.structures.index')
            ->with('success', 'Structure créée.');
    }

    public function show(Structure $structure)
    {
        $this->authorize('view', $structure);
        $structure->load(['typeStructure', 'parent', 'enfants', 'postes']);
        return view('organisation.structures.show', compact('structure'));
    }

    public function edit(Structure $structure)
    {
        $this->authorize('update', $structure);
        $types = TypeStructure::orderBy('ordre')->get();
        $parents = Structure::where('id', '!=', $structure->id)->orderBy('nom')->get();
        return view('organisation.structures.edit', compact('structure', 'types', 'parents'));
    }

    public function update(StoreStructureRequest $request, Structure $structure)
    {
        $this->authorize('update', $structure);
        $structure->update($request->validated());
        return redirect()->route('organisation.structures.index')
            ->with('success', 'Structure mise à jour.');
    }

    public function destroy(Structure $structure)
    {
        $this->authorize('delete', $structure);
        if ($structure->enfants()->exists() || $structure->postes()->exists()) {
            return back()->with('error', 'Impossible de supprimer : la structure contient des enfants ou des postes.');
        }
        $structure->marquerSupprime();
        return redirect()->route('organisation.structures.index')
            ->with('success', 'Structure supprimée.');
    }

    public function fusionner(Request $request, Structure $structure, FusionnerStructures $action)
    {
        $this->authorize('update', $structure);
        $donnees = $request->validate([
            'cible_id' => ['required', 'exists:structures,id'],
        ]);
        $cible = Structure::findOrFail($donnees['cible_id']);
        $action->executer($structure, $cible);
        return redirect()->route('organisation.structures.index')
            ->with('success', 'Structures fusionnées.');
    }

    public function organigramme()
    {
        $this->authorize('viewAny', Structure::class);
        $racines = Structure::whereNull('parent_id')
            ->with(['enfantsRecursifs', 'typeStructure'])
            ->orderBy('nom')
            ->get();
        return view('organisation.structures.organigramme', compact('racines'));
    }
}