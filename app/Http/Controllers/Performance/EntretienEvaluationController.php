<?php

namespace App\Http\Controllers\Performance;

use App\Domain\Performance\Actions\CreerEntretienEvaluation;
use App\Domain\Performance\Actions\RealiserEntretienEvaluation;
use App\Domain\Performance\Actions\SaisirAutoEvaluation;
use App\Domain\Performance\Enums\StatutEntretien;
use App\Domain\Performance\Events\EntretienValide;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Requests\RealiserEntretienRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntretienEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EntretienEvaluation::class);

        $entretiens = EntretienEvaluation::query()
            ->with(['campagne', 'salarie'])
            ->campagne($request->campagne_id ? (int) $request->campagne_id : null)
            ->deStatut($request->statut)
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('performance.entretiens.index', [
            'entretiens' => $entretiens,
            'campagnes' => CampagneEvaluation::orderByDesc('annee')->get(),
            'statuts' => StatutEntretien::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', EntretienEvaluation::class);

        $donnees = $request->validate([
            'campagne_id' => ['required', 'exists:campagnes_evaluation,id'],
            'salarie_id' => ['required', 'exists:salaries,id'],
        ]);

        $entretien = app(CreerEntretienEvaluation::class)->executer($donnees);

        return redirect()->route('performance.entretiens.show', $entretien);
    }

    public function show(EntretienEvaluation $entretien)
    {
        $this->authorize('view', $entretien);
        $entretien->load(['campagne', 'salarie']);
        return view('performance.entretiens.show', compact('entretien'));
    }

    public function autoEvaluer(Request $request, EntretienEvaluation $entretien, SaisirAutoEvaluation $action)
    {
        $this->authorize('saisirAutoEvaluation', $entretien);

        $donnees = $request->validate([
            'note_auto_evaluation' => ['required', 'numeric', 'min:0', 'max:20'],
        ]);

        try {
            $action->executer($entretien, (float) $donnees['note_auto_evaluation']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Auto-évaluation enregistrée.']);
        }

        return back()->with('success', 'Auto-évaluation enregistrée.');
    }

    public function realiser(RealiserEntretienRequest $request, EntretienEvaluation $entretien, RealiserEntretienEvaluation $action)
    {
        $this->authorize('update', $entretien);

        try {
            $action->executer($entretien, $request->validated());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Entretien réalisé.']);
        }

        return back()->with('success', 'Entretien réalisé.');
    }

    public function valider(Request $request, EntretienEvaluation $entretien, RealiserEntretienEvaluation $action)
    {
        $this->authorize('valider', $entretien);

        try {
            $action->valider($entretien);
            event(new EntretienValide($entretien->fresh()));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Entretien validé.']);
        }

        return back()->with('success', 'Entretien validé.');
    }

    public function destroy(EntretienEvaluation $entretien)
    {
        $this->authorize('delete', $entretien);
        $entretien->marquerSupprime();

        return redirect()->route('performance.entretiens.index')
            ->with('success', 'Entretien supprimé.');
    }
}