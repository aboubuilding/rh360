<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\RegleContrat;
use App\Domain\Contrats\Requests\StoreRegleContratRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegleContratController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'contrats.validate');

        $regles = RegleContrat::query()
            ->with(['categorie', 'creePar'])
            ->when($request->filled('type_contrat'), fn ($q) => $q->where('type_contrat', $request->type_contrat))
            ->orderByDesc('date_effet')
            ->paginate(50)
            ->withQueryString();

        return view('contrats.regles.index', [
            'regles' => $regles,
            'types' => TypeContrat::options(),
        ]);
    }

    public function store(StoreRegleContratRequest $request)
    {
        $this->authorize('permission', 'contrats.validate');

        RegleContrat::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'cree_par' => auth()->id(),
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle créée.']);
        }

        return redirect()->route('contrats.regles.index')
            ->with('success', 'Règle créée.');
    }

    public function update(StoreRegleContratRequest $request, RegleContrat $regle)
    {
        $this->authorize('permission', 'contrats.validate');

        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }

        return redirect()->route('contrats.regles.index')
            ->with('success', 'Règle mise à jour.');
    }

    public function destroy(RegleContrat $regle)
    {
        $this->authorize('permission', 'contrats.validate');

        $regle->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Règle supprimée.']);
        }

        return redirect()->route('contrats.regles.index')
            ->with('success', 'Règle supprimée.');
    }
}