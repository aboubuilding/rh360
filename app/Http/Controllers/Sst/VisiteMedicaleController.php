<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Actions\ProgrammerVisiteMedicale;
use App\Domain\Sst\Actions\RenseignerVisiteMedicale;
use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Sst\Events\VisiteMedicaleRealisee;
use App\Domain\Sst\Models\VisiteMedicale;
use App\Domain\Sst\Requests\RenseignerVisiteMedicaleRequest;
use App\Domain\Sst\Requests\StoreVisiteMedicaleRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VisiteMedicaleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', VisiteMedicale::class);

        $visites = VisiteMedicale::query()
            ->with('salarie')
            ->recherche($request->q)
            ->deStatut($request->statut)
            ->deType($request->type_visite)
            ->when($request->filled('echeance'), function ($q) use ($request) {
                if ($request->echeance === 'proches') {
                    $q->echeancesProches(30);
                } elseif ($request->echeance === 'echues') {
                    $q->echues();
                }
            })
            ->orderBy('date_prevue')
            ->paginate(50)
            ->withQueryString();

        return view('sst.visites.index', [
            'visites' => $visites,
            'types' => TypeVisiteMedicale::options(),
            'statuts' => StatutVisiteMedicale::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', VisiteMedicale::class);

        $salarie = $request->filled('salarie_id')
            ? Salarie::findOrFail($request->salarie_id)
            : null;

        return view('sst.visites.create', [
            'salarie' => $salarie,
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'types' => TypeVisiteMedicale::options(),
        ]);
    }

    public function store(StoreVisiteMedicaleRequest $request, ProgrammerVisiteMedicale $action)
    {
        $this->authorize('create', VisiteMedicale::class);

        $visite = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Visite programmée.',
                'redirect' => route('sst.visites.show', $visite),
            ]);
        }

        return redirect()->route('sst.visites.show', $visite)
            ->with('success', 'Visite programmée.');
    }

    public function show(VisiteMedicale $visite)
    {
        $this->authorize('view', $visite);

        $visite->load(['salarie', 'visiteOrigine', 'creePar', 'modifiePar']);

        return view('sst.visites.show', [
            'visite' => $visite,
            'aptitudes' => AptitudeMedicale::options(),
        ]);
    }

    public function renseigner(RenseignerVisiteMedicaleRequest $request, VisiteMedicale $visite, RenseignerVisiteMedicale $action)
    {
        $this->authorize('update', $visite);

        try {
            $action->executer(
                $visite,
                AptitudeMedicale::from($request->aptitude),
                $request->date_realisation,
                $request->reference_avis,
                $request->restrictions,
                $request->prestataire,
            );

            event(new VisiteMedicaleRealisee($visite->fresh()));
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Visite renseignée.']);
        }

        return back()->with('success', 'Visite renseignée.');
    }

    public function annuler(Request $request, VisiteMedicale $visite, RenseignerVisiteMedicale $action)
    {
        $this->authorize('update', $visite);

        $donnees = $request->validate([
            'motif_annulation' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'motif_annulation.required' => 'Le motif d\'annulation est obligatoire.',
            'motif_annulation.min' => 'Le motif doit contenir au moins 5 caractères.',
        ]);

        try {
            $action->annuler($visite, $donnees['motif_annulation']);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Visite annulée.']);
        }

        return back()->with('success', 'Visite annulée.');
    }

    public function destroy(VisiteMedicale $visite)
    {
        $this->authorize('delete', $visite);
        $visite->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Visite supprimée.']);
        }

        return redirect()->route('sst.visites.index')
            ->with('success', 'Visite supprimée.');
    }
}