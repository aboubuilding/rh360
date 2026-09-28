<?php

namespace App\Http\Controllers\Performance;

use App\Domain\Performance\Actions\CreerObjectifEvaluation;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\ObjectifEvaluation;
use App\Domain\Performance\Requests\StoreObjectifEvaluationRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ObjectifEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ObjectifEvaluation::class);

        $objectifs = ObjectifEvaluation::query()
            ->with(['campagne', 'salarie'])
            ->when($request->filled('campagne_id'), fn ($q) => $q->where('campagne_id', $request->campagne_id))
            ->when($request->filled('salarie_id'), fn ($q) => $q->where('salarie_id', $request->salarie_id))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('performance.objectifs.index', [
            'objectifs' => $objectifs,
            'campagnes' => CampagneEvaluation::orderByDesc('annee')->get(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreObjectifEvaluationRequest $request, CreerObjectifEvaluation $action)
    {
        $this->authorize('create', ObjectifEvaluation::class);

        $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Objectif créé.']);
        }

        return back()->with('success', 'Objectif créé.');
    }

    public function update(Request $request, ObjectifEvaluation $objectif)
    {
        $this->authorize('update', $objectif);

        $objectif->update($request->validate([
            'intitule' => ['required', 'string', 'max:255'],
            'indicateur' => ['nullable', 'string', 'max:255'],
            'cible' => ['nullable', 'string', 'max:255'],
            'ponderation' => ['required', 'numeric', 'min:0', 'max:10'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['required', 'string', 'max:60'],
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Objectif mis à jour.']);
        }

        return back()->with('success', 'Objectif mis à jour.');
    }

    public function destroy(ObjectifEvaluation $objectif)
    {
        $this->authorize('delete', $objectif);
        $objectif->marquerSupprime();

        return back()->with('success', 'Objectif supprimé.');
    }
}