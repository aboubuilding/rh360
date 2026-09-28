<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Actions\ConfirmerFiabilite;
use App\Domain\Carriere\Actions\ReprendreSituation;
use App\Domain\Carriere\Enums\StatutFiabilite;
use App\Domain\Carriere\Enums\StatutHistorique;
use App\Domain\Carriere\Enums\TypeSourceSituation;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Requests\ReprendreSituationRequest;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SituationCarriereController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SituationCarriere::class);

        $situations = SituationCarriere::query()
            ->with(['salarie', 'positionClassification', 'enregistrePar'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('salarie', function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->q}%")
                  ->orWhere('prenoms', 'like', "%{$request->q}%")
                  ->orWhere('matricule', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('fiabilite'), fn ($q) => $q->where('statut_fiabilite', $request->fiabilite))
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString();

        return view('carriere.situations.index', [
            'situations' => $situations,
            'fiabilites' => StatutFiabilite::options(),
        ]);
    }

    public function show(Salarie $salarie)
    {
        $situation = SituationCarriere::firstOrCreate(
            ['salarie_id' => $salarie->id],
            [
                'entreprise_id' => $salarie->entreprise_id,
                'enregistre_le' => now(),
                'etat' => 1,
            ]
        );

        $this->authorize('view', $situation);
        $situation->load(['positionClassification', 'positionOuverture', 'enregistrePar']);

        // Chronologie de carrière
        $mouvements = \App\Domain\Carriere\Models\MouvementCarriere::where('salarie_id', $salarie->id)
            ->orderByDesc('date_effet')
            ->get();

        return view('carriere.situations.show', compact('salarie', 'situation', 'mouvements'));
    }

    public function reprendre(Salarie $salarie)
    {
        $this->authorize('reprendre', SituationCarriere::class);

        $situation = SituationCarriere::where('salarie_id', $salarie->id)->first();

        return view('carriere.situations.reprendre', [
            'salarie' => $salarie,
            'situation' => $situation,
            'positions' => PositionClassification::orderBy('ordre')->get(),
        ]);
    }

    public function enregistrerReprise(ReprendreSituationRequest $request, Salarie $salarie, ReprendreSituation $action)
    {
        $this->authorize('reprendre', SituationCarriere::class);

        $donnees = $request->validated();

        if ($request->hasFile('justificatif')) {
            $donnees['chemin_justificatif'] = $request->file('justificatif')
                ->store('carriere/justificatifs', 'local');
        }
        unset($donnees['justificatif']);

        $action->executer($salarie->id, $donnees);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Situation reconstituée.',
                'redirect' => route('carriere.situations.show', $salarie),
            ]);
        }

        return redirect()->route('carriere.situations.show', $salarie)
            ->with('success', 'Situation reconstituée.');
    }

    public function confirmerFiabilite(Request $request, SituationCarriere $situation, ConfirmerFiabilite $action)
    {
        $this->authorize('confirmerFiabilite', $situation);

        $action->executer($situation, $request->input('note'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fiabilité confirmée.']);
        }
        return back()->with('success', 'Fiabilité confirmée.');
    }
}