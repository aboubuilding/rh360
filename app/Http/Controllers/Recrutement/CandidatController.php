<?php

namespace App\Http\Controllers\Recrutement;

use App\Domain\Recrutement\Actions\ChangerEtapeCandidat;
use App\Domain\Recrutement\Actions\CreerCandidat;
use App\Domain\Recrutement\Actions\DeciderCandidat;
use App\Domain\Recrutement\Actions\IntegrerCandidat;
use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Enums\SourceCandidat;
use App\Domain\Recrutement\Events\CandidatIntegre;
use App\Domain\Recrutement\Events\CandidatRetenu;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Requests\ChangerEtapeCandidatRequest;
use App\Domain\Recrutement\Requests\IntegrerCandidatRequest;
use App\Domain\Recrutement\Requests\StoreCandidatRequest;
use App\Domain\Recrutement\Services\SuiviEtapesCandidat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CandidatController extends Controller
{
    public function __construct(private SuiviEtapesCandidat $suivi) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Candidat::class);

        $candidats = Candidat::query()
            ->with('besoin')
            ->recherche($request->q)
            ->parEtape($request->etape)
            ->parDecision($request->decision)
            ->when($request->filled('besoin_id'), fn ($q) => $q->where('besoin_id', $request->besoin_id))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('recrutement.candidats.index', [
            'candidats' => $candidats,
            'besoins' => BesoinRecrutement::orderByDesc('created_at')->get(),
            'etapes' => EtapeCandidat::options(),
            'decisions' => DecisionCandidat::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Candidat::class);

        return view('recrutement.candidats.create', [
            'besoins' => BesoinRecrutement::whereIn('statut', ['valide', 'en_cours'])->orderBy('intitule_poste')->get(),
            'sources' => SourceCandidat::options(),
        ]);
    }

    public function store(StoreCandidatRequest $request, CreerCandidat $action)
    {
        $this->authorize('create', Candidat::class);

        $candidat = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Candidat créé.',
                'redirect' => route('recrutement.candidats.show', $candidat),
            ]);
        }

        return redirect()->route('recrutement.candidats.show', $candidat)
            ->with('success', 'Candidat créé.');
    }

    public function show(Candidat $candidat)
    {
        $this->authorize('view', $candidat);
        $candidat->load('besoin');

        $etapesPossibles = $this->suivi->etapesPossibles($candidat);

        return view('recrutement.candidats.show', [
            'candidat' => $candidat,
            'etapesPossibles' => $etapesPossibles,
        ]);
    }

    public function changerEtape(ChangerEtapeCandidatRequest $request, Candidat $candidat, ChangerEtapeCandidat $action)
    {
        $this->authorize('changerEtape', $candidat);

        try {
            $action->executer(
                $candidat,
                EtapeCandidat::from($request->etape),
                $request->observations,
            );

            if ($request->filled('score')) {
                $candidat->update(['score' => $request->score]);
            }
            if ($request->filled('date_entretien')) {
                $candidat->update(['date_entretien' => $request->date_entretien]);
            }
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Étape mise à jour.']);
        }

        return back()->with('success', 'Étape mise à jour.');
    }

    public function retenir(Request $request, Candidat $candidat, DeciderCandidat $action)
    {
        $this->authorize('decider', $candidat);

        $donnees = $request->validate([
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $action->retenir($candidat, $donnees['commentaire'] ?? null);
            event(new CandidatRetenu($candidat->fresh()));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Candidat retenu.']);
        }

        return back()->with('success', 'Candidat retenu.');
    }

    public function refuser(Request $request, Candidat $candidat, DeciderCandidat $action)
    {
        $this->authorize('decider', $candidat);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motif.required' => 'Le motif de refus est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
        ]);

        try {
            $action->refuser($candidat, $donnees['motif']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Candidat refusé.']);
        }

        return back()->with('success', 'Candidat refusé.');
    }

    public function integrer(IntegrerCandidatRequest $request, Candidat $candidat, IntegrerCandidat $action)
    {
        $this->authorize('integrer', $candidat);

        try {
            $action->executer($candidat, $request->date_integration);
            event(new CandidatIntegre($candidat->fresh()));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Candidat intégré.']);
        }

        return back()->with('success', 'Candidat intégré.');
    }

    public function destroy(Candidat $candidat)
    {
        $this->authorize('delete', $candidat);
        $candidat->marquerSupprime();

        return redirect()->route('recrutement.candidats.index')
            ->with('success', 'Candidat supprimé.');
    }
}