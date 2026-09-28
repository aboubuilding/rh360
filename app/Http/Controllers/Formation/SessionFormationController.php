<?php

namespace App\Http\Controllers\Formation;

use App\Domain\Formation\Actions\AjouterParticipantFormation;
use App\Domain\Formation\Actions\CreerSessionFormation;
use App\Domain\Formation\Enums\StatutSessionFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Formation\Requests\AjouterParticipantRequest;
use App\Domain\Formation\Requests\StoreSessionFormationRequest;
use App\Domain\Formation\Services\CalculateurProgressionParticipant;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SessionFormationController extends Controller
{
    public function __construct(private CalculateurProgressionParticipant $calculateur) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', SessionFormation::class);

        $sessions = SessionFormation::query()
            ->with(['planFormation'])
            ->recherche($request->q)
            ->deStatut($request->statut)
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        return view('formation.sessions.index', [
            'sessions' => $sessions,
            'statuts' => StatutSessionFormation::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', SessionFormation::class);

        return view('formation.sessions.create', [
            'plans' => PlanFormation::orderByDesc('annee')->get(),
        ]);
    }

    public function store(StoreSessionFormationRequest $request, CreerSessionFormation $action)
    {
        $this->authorize('create', SessionFormation::class);

        try {
            $session = $action->executer($request->validated());
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Session créée.',
                'redirect' => route('formation.sessions.show', $session),
            ]);
        }

        return redirect()->route('formation.sessions.show', $session)
            ->with('success', 'Session créée.');
    }

    public function show(SessionFormation $session)
    {
        $this->authorize('view', $session);

        $session->load(['planFormation', 'participants.salarie']);

        $stats = $this->calculateur->analyserSession($session);

        return view('formation.sessions.show', [
            'session' => $session,
            'stats' => $stats,
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function ajouterParticipant(AjouterParticipantRequest $request, SessionFormation $session, AjouterParticipantFormation $action)
    {
        $this->authorize('gererParticipants', $session);

        try {
            $action->executer($session, (int) $request->salarie_id);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Participant ajouté.']);
        }

        return back()->with('success', 'Participant ajouté.');
    }

    public function destroy(SessionFormation $session)
    {
        $this->authorize('delete', $session);
        $session->marquerSupprime();

        return redirect()->route('formation.sessions.index')
            ->with('success', 'Session supprimée.');
    }
}