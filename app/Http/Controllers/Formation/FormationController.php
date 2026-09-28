<?php

namespace App\Http\Controllers\Formation;

use App\Domain\Formation\Actions\CreerFormation;
use App\Domain\Formation\Enums\ModaliteFormation;
use App\Domain\Formation\Models\Formation;
use App\Domain\Formation\Requests\StoreFormationRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Formation::class);

        $formations = Formation::query()
            ->recherche($request->q)
            ->orderBy('intitule')
            ->paginate(50)
            ->withQueryString();

        return view('formation.formations.index', [
            'formations' => $formations,
            'modalites' => ModaliteFormation::options(),
        ]);
    }

    public function store(StoreFormationRequest $request, CreerFormation $action)
    {
        $this->authorize('create', Formation::class);

        $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Formation créée.']);
        }

        return redirect()->route('formation.formations.index')
            ->with('success', 'Formation créée.');
    }

    public function update(StoreFormationRequest $request, Formation $formation)
    {
        $this->authorize('update', $formation);

        $formation->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Formation mise à jour.']);
        }

        return redirect()->route('formation.formations.index')
            ->with('success', 'Formation mise à jour.');
    }

    public function destroy(Formation $formation)
    {
        $this->authorize('delete', $formation);
        $formation->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Formation supprimée.']);
        }

        return redirect()->route('formation.formations.index')
            ->with('success', 'Formation supprimée.');
    }
}