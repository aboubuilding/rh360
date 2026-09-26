<?php

namespace App\Http\Controllers\Organisation;

use App\Domain\Organisation\Models\TypeStructure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TypeStructureController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', TypeStructure::class);
        $types = TypeStructure::orderBy('ordre')->orderBy('nom')->paginate(50);
        return view('organisation.types-structures.index', compact('types'));
    }

    public function create()
    {
        $this->authorize('create', TypeStructure::class);
        return view('organisation.types-structures.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', TypeStructure::class);
        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:240'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ]);
        TypeStructure::create($donnees);
        return redirect()->route('organisation.types-structures.index')
            ->with('success', 'Type de structure créé.');
    }

    public function edit(TypeStructure $typeStructure)
    {
        $this->authorize('update', $typeStructure);
        return view('organisation.types-structures.edit', compact('typeStructure'));
    }

    public function update(Request $request, TypeStructure $typeStructure)
    {
        $this->authorize('update', $typeStructure);
        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:240'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ]);
        $typeStructure->update($donnees);
        return redirect()->route('organisation.types-structures.index')
            ->with('success', 'Type de structure mis à jour.');
    }

    public function destroy(TypeStructure $typeStructure)
    {
        $this->authorize('delete', $typeStructure);
        $typeStructure->marquerSupprime();
        return redirect()->route('organisation.types-structures.index')
            ->with('success', 'Type de structure supprimé.');
    }
}