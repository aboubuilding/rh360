<?php

namespace App\Http\Controllers\Performance;

use App\Domain\Performance\Actions\CreerCampagneEvaluation;
use App\Domain\Performance\Enums\StatutCampagne;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Requests\StoreCampagneEvaluationRequest;
use App\Domain\Performance\Services\GenerateurStatistiquesPerformance;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CampagneEvaluationController extends Controller
{
    public function __construct(private GenerateurStatistiquesPerformance $stats) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CampagneEvaluation::class);

        $campagnes = CampagneEvaluation::query()
            ->annee($request->annee ? (int) $request->annee : null)
            ->orderByDesc('annee')
            ->paginate(20)
            ->withQueryString();

        return view('performance.campagnes.index', [
            'campagnes' => $campagnes,
            'statuts' => StatutCampagne::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', CampagneEvaluation::class);
        return view('performance.campagnes.create');
    }

    public function store(StoreCampagneEvaluationRequest $request, CreerCampagneEvaluation $action)
    {
        $this->authorize('create', CampagneEvaluation::class);

        $campagne = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Campagne créée.',
                'redirect' => route('performance.campagnes.show', $campagne),
            ]);
        }

        return redirect()->route('performance.campagnes.show', $campagne)
            ->with('success', 'Campagne créée.');
    }

    public function show(CampagneEvaluation $campagne)
    {
        $this->authorize('view', $campagne);

        $campagne->load(['criteres', 'entretiens.salarie']);
        $statistiques = $this->stats->campagne($campagne);

        // Salariés éligibles non encore évalués
        $dejaEvalues = $campagne->entretiens()->pluck('salarie_id')->toArray();
        $salariesEligibles = Salarie::where('actif', true)
            ->whereNotIn('id', $dejaEvalues)
            ->orderBy('nom')
            ->get();

        return view('performance.campagnes.show', [
            'campagne' => $campagne,
            'statistiques' => $statistiques,
            'salariesEligibles' => $salariesEligibles,
        ]);
    }

    public function cloturer(Request $request, CampagneEvaluation $campagne)
    {
        $this->authorize('cloturer', $campagne);

        $campagne->update(['statut' => StatutCampagne::CLOTUREE->value]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Campagne clôturée.']);
        }

        return back()->with('success', 'Campagne clôturée.');
    }

    public function archiver(Request $request, CampagneEvaluation $campagne)
    {
        $this->authorize('archiver', $campagne);

        $campagne->update(['statut' => StatutCampagne::ARCHIVEE->value]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Campagne archivée.']);
        }

        return back()->with('success', 'Campagne archivée.');
    }

    public function destroy(CampagneEvaluation $campagne)
    {
        $this->authorize('delete', $campagne);
        $campagne->marquerSupprime();

        return redirect()->route('performance.campagnes.index')
            ->with('success', 'Campagne supprimée.');
    }
}