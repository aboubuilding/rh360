<?php

namespace App\Http\Controllers\Performance;

use App\Domain\Performance\Actions\CreerCritereEvaluation;
use App\Domain\Performance\Enums\FamilleCritere;
use App\Domain\Performance\Models\CritereEvaluation;
use App\Domain\Performance\Requests\StoreCritereEvaluationRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CritereEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CritereEvaluation::class);

        $criteres = CritereEvaluation::query()
            ->orderBy('libelle')
            ->paginate(50);

        return view('performance.criteres.index', [
            'criteres' => $criteres,
            'familles' => FamilleCritere::options(),
        ]);
    }

    public function store(StoreCritereEvaluationRequest $request, CreerCritereEvaluation $action)
    {
        $this->authorize('create', CritereEvaluation::class);

        $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Critère créé.']);
        }

        return back()->with('success', 'Critère créé.');
    }

    public function update(StoreCritereEvaluationRequest $request, CritereEvaluation $critere)
    {
        $this->authorize('update', $critere);

        $critere->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Critère mis à jour.']);
        }

        return back()->with('success', 'Critère mis à jour.');
    }

    public function destroy(CritereEvaluation $critere)
    {
        $this->authorize('delete', $critere);
        $critere->marquerSupprime();

        return back()->with('success', 'Critère supprimé.');
    }
}