<?php

namespace App\Http\Controllers\Recrutement;

use App\Domain\Recrutement\Actions\CreerBesoinRecrutement;
use App\Domain\Recrutement\Actions\ValiderBesoinRecrutement;
use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Requests\StoreBesoinRecrutementRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BesoinRecrutementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', BesoinRecrutement::class);

        $besoins = BesoinRecrutement::query()
            ->with('candidats')
            ->recherche($request->q)
            ->deStatut($request->statut)
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('recrutement.besoins.index', [
            'besoins' => $besoins,
            'statuts' => StatutBesoinRecrutement::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', BesoinRecrutement::class);
        return view('recrutement.besoins.create');
    }

    public function store(StoreBesoinRecrutementRequest $request, CreerBesoinRecrutement $action)
    {
        $this->authorize('create', BesoinRecrutement::class);

        $besoin = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Besoin créé.',
                'redirect' => route('recrutement.besoins.show', $besoin),
            ]);
        }

        return redirect()->route('recrutement.besoins.show', $besoin)
            ->with('success', 'Besoin créé.');
    }

    public function show(BesoinRecrutement $besoin)
    {
        $this->authorize('view', $besoin);
        $besoin->load(['candidats']);
        return view('recrutement.besoins.show', compact('besoin'));
    }

    public function valider(Request $request, BesoinRecrutement $besoin, ValiderBesoinRecrutement $action)
    {
        $this->authorize('valider', $besoin);

        try {
            $action->executer($besoin);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Besoin validé.']);
        }

        return back()->with('success', 'Besoin validé.');
    }

    public function ouvrir(Request $request, BesoinRecrutement $besoin, ValiderBesoinRecrutement $action)
    {
        $this->authorize('valider', $besoin);

        try {
            $action->ouvrirRecrutement($besoin);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Recrutement ouvert.']);
        }

        return back()->with('success', 'Recrutement ouvert.');
    }

    public function destroy(BesoinRecrutement $besoin)
    {
        $this->authorize('delete', $besoin);
        $besoin->marquerSupprime();

        return redirect()->route('recrutement.besoins.index')
            ->with('success', 'Besoin supprimé.');
    }
}