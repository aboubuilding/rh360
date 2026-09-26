<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Actions\DeclarerEvenementEssai;
use App\Domain\Contrats\Actions\DeciderEvenementEssai;
use App\Domain\Contrats\Enums\NatureEvenementEssai;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\EvenementEssai;
use App\Domain\Contrats\Requests\DeciderEvenementEssaiRequest;
use App\Domain\Contrats\Requests\StoreEvenementEssaiRequest;
use App\Domain\Contrats\Services\CalculateurFinEssai;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EvenementEssaiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EvenementEssai::class);

        $evenements = EvenementEssai::query()
            ->with(['contrat.salarie', 'creePar', 'decidePar'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('nature'), fn ($q) => $q->where('nature', $request->nature))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('contrats.evenements-essai.index', [
            'evenements' => $evenements,
            'natures' => NatureEvenementEssai::options(),
        ]);
    }

    public function create(Contrat $contrat)
    {
        $this->authorize('view', $contrat);
        $this->authorize('declarer', EvenementEssai::class);

        $calculEssai = app(CalculateurFinEssai::class)->calculer($contrat);

        return view('contrats.evenements-essai.create', [
            'contrat' => $contrat,
            'calculEssai' => $calculEssai,
            'natures' => NatureEvenementEssai::options(),
        ]);
    }

    public function store(StoreEvenementEssaiRequest $request, Contrat $contrat, DeclarerEvenementEssai $action)
    {
        $this->authorize('view', $contrat);
        $this->authorize('declarer', EvenementEssai::class);

        $details = array_filter([
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'duree_jours' => $request->duree_jours,
            'commentaire' => $request->commentaire,
        ], fn ($v) => ! is_null($v));

        $action->executer(
            $contrat,
            NatureEvenementEssai::from($request->nature),
            $details,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Événement déclaré.']);
        }

        return redirect()->route('contrats.contrats.show', $contrat)
            ->with('success', 'Événement déclaré.');
    }

    public function valider(DeciderEvenementEssaiRequest $request, EvenementEssai $evenement, DeciderEvenementEssai $action)
    {
        $this->authorize('decider', $evenement);

        $action->valider($evenement, $request->note_decision);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Événement validé.']);
        }

        return back()->with('success', 'Événement validé.');
    }

    public function refuser(DeciderEvenementEssaiRequest $request, EvenementEssai $evenement, DeciderEvenementEssai $action)
    {
        $this->authorize('decider', $evenement);

        $action->refuser($evenement, $request->note_decision);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Événement refusé.']);
        }

        return back()->with('success', 'Événement refusé.');
    }
}