<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Actions\CreerRubriquePaie;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Requests\StoreRubriquePaieRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RubriquePaieController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RubriquePaie::class);

        $rubriques = RubriquePaie::query()
            ->recherche($request->q)
            ->deNature($request->nature)
            ->orderBy('nature')->orderBy('nom')
            ->paginate(50)
            ->withQueryString();

        return view('paie.rubriques.index', [
            'rubriques' => $rubriques,
            'natures' => NatureRubrique::options(),
        ]);
    }

    public function store(StoreRubriquePaieRequest $request, CreerRubriquePaie $action)
    {
        $this->authorize('create', RubriquePaie::class);

        $rubrique = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Rubrique créée.']);
        }

        return redirect()->route('paie.rubriques.index')
            ->with('success', 'Rubrique créée.');
    }

    public function update(StoreRubriquePaieRequest $request, RubriquePaie $rubrique)
    {
        $this->authorize('update', $rubrique);

        $rubrique->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Rubrique mise à jour.']);
        }

        return redirect()->route('paie.rubriques.index')
            ->with('success', 'Rubrique mise à jour.');
    }

    public function destroy(RubriquePaie $rubrique)
    {
        $this->authorize('delete', $rubrique);

        $rubrique->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Rubrique supprimée.']);
        }

        return redirect()->route('paie.rubriques.index')
            ->with('success', 'Rubrique supprimée.');
    }
}