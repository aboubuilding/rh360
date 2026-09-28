<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Requests\StoreTypeCongeRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TypeCongeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TypeConge::class);

        $types = TypeConge::query()
            ->recherche($request->q)
            ->deCategorie($request->categorie)
            ->orderBy('categorie')->orderBy('nom')
            ->paginate(50)
            ->withQueryString();

        return view('conges.types.index', [
            'types' => $types,
            'categories' => CategorieTypeConge::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', TypeConge::class);
        return view('conges.types.create', [
            'categories' => CategorieTypeConge::options(),
        ]);
    }

    public function store(StoreTypeCongeRequest $request)
    {
        $this->authorize('create', TypeConge::class);

        TypeConge::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Type de congé créé.']);
        }

        return redirect()->route('conges.types.index')
            ->with('success', 'Type de congé créé.');
    }

    public function edit(TypeConge $type)
    {
        $this->authorize('update', $type);
        return view('conges.types.edit', [
            'type' => $type,
            'categories' => CategorieTypeConge::options(),
        ]);
    }

    public function update(StoreTypeCongeRequest $request, TypeConge $type)
    {
        $this->authorize('update', $type);
        $type->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Type de congé mis à jour.']);
        }

        return redirect()->route('conges.types.index')
            ->with('success', 'Type de congé mis à jour.');
    }

    public function destroy(TypeConge $type)
    {
        $this->authorize('delete', $type);
        $type->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Type supprimé.']);
        }

        return redirect()->route('conges.types.index')
            ->with('success', 'Type supprimé.');
    }
}