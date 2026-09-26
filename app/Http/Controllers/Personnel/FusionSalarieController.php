<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Personnel\Actions\FusionnerSalaries;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Requests\FusionnerSalariesRequest;
use App\Http\Controllers\Controller;

class FusionSalarieController extends Controller
{
    public function formulaire(Salarie $source)
    {
        $this->authorize('fusionner', Salarie::class);
        $this->authorize('view', $source);

        // Candidats : tous sauf le source lui-même
        $candidats = Salarie::query()
            ->where('id', '!=', $source->id)
            ->orderBy('nom')
            ->get();

        return view('personnel.salaries.fusion', compact('source', 'candidats'));
    }

    public function fusionner(FusionnerSalariesRequest $request, Salarie $source, FusionnerSalaries $action)
    {
        $this->authorize('fusionner', Salarie::class);
        $this->authorize('view', $source);

        $cible = Salarie::findOrFail($request->cible_id);
        $this->authorize('update', $cible);

        $action->executer($source, $cible, $request->motif);

        return redirect()->route('personnel.salaries.show', $cible)
            ->with('success', 'Fiches fusionnées. La fiche source est conservée en archive.');
    }
}