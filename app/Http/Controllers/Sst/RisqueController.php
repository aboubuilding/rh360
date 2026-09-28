<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Sst\Actions\AjouterActionRisque;
use App\Domain\Sst\Actions\ArchiverRisque;
use App\Domain\Sst\Actions\CreerRisque;
use App\Domain\Sst\Actions\EvaluerRisque;
use App\Domain\Sst\Enums\FamilleRisque;
use App\Domain\Sst\Enums\NiveauRisque;
use App\Domain\Sst\Enums\TypeMesurePrevention;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Requests\EvaluerRisqueRequest;
use App\Domain\Sst\Requests\StoreActionRisqueRequest;
use App\Domain\Sst\Requests\StoreRisqueRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RisqueController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Risque::class);

        $risques = Risque::query()
            ->with(['poste', 'responsable'])
            ->recherche($request->q)
            ->parFamille($request->famille)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->orderBy('intitule')
            ->paginate(50)
            ->withQueryString();

        return view('sst.risques.index', [
            'risques' => $risques,
            'familles' => FamilleRisque::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Risque::class);

        return view('sst.risques.create', [
            'familles' => FamilleRisque::options(),
            'postes' => Poste::orderBy('intitule')->get(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreRisqueRequest $request, CreerRisque $action)
    {
        $this->authorize('create', Risque::class);

        $risque = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Risque enregistré.',
                'redirect' => route('sst.risques.show', $risque),
            ]);
        }

        return redirect()->route('sst.risques.show', $risque)
            ->with('success', 'Risque enregistré.');
    }

    public function show(Risque $risque)
    {
        $this->authorize('view', $risque);

        $risque->load([
            'poste', 'responsable', 'evaluations.creePar',
            'actions.responsable', 'creePar', 'modifiePar',
        ]);

        return view('sst.risques.show', [
            'risque' => $risque,
            'niveaux' => NiveauRisque::options(),
            'typesMesure' => TypeMesurePrevention::options(),
        ]);
    }

    public function evaluer(EvaluerRisqueRequest $request, Risque $risque, EvaluerRisque $action)
    {
        $this->authorize('evaluer', $risque);

        $action->executer($risque, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Évaluation enregistrée.']);
        }
        return back()->with('success', 'Évaluation enregistrée.');
    }

    public function ajouterAction(StoreActionRisqueRequest $request, Risque $risque, AjouterActionRisque $action)
    {
        $this->authorize('gererActions', $risque);

        $action->executer($risque, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action ajoutée.']);
        }
        return back()->with('success', 'Action ajoutée.');
    }

    public function archiver(Request $request, Risque $risque, ArchiverRisque $action)
    {
        $this->authorize('archiver', $risque);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $action->executer($risque, $donnees['motif']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Risque archivé.']);
        }
        return back()->with('success', 'Risque archivé.');
    }

    public function destroy(Risque $risque)
    {
        $this->authorize('delete', $risque);
        $risque->marquerSupprime();

        return redirect()->route('sst.risques.index')
            ->with('success', 'Risque supprimé.');
    }
}