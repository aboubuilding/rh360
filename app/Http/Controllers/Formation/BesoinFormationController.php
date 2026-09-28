<?php

namespace App\Http\Controllers\Formation;

use App\Domain\Formation\Actions\CreerBesoinFormation;
use App\Domain\Formation\Actions\ValiderBesoinFormation;
use App\Domain\Formation\Enums\PrioriteBesoinFormation;
use App\Domain\Formation\Enums\StatutBesoinFormation;
use App\Domain\Formation\Events\BesoinFormationValide;
use App\Domain\Formation\Models\BesoinFormation;
use App\Domain\Formation\Requests\StoreBesoinFormationRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BesoinFormationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', BesoinFormation::class);

        $besoins = BesoinFormation::query()
            ->with('salarie')
            ->recherche($request->q)
            ->deStatut($request->statut)
            ->annee($request->annee ? (int) $request->annee : null)
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('formation.besoins.index', [
            'besoins' => $besoins,
            'statuts' => StatutBesoinFormation::options(),
            'priorites' => PrioriteBesoinFormation::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', BesoinFormation::class);

        return view('formation.besoins.create', [
            'priorites' => PrioriteBesoinFormation::options(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreBesoinFormationRequest $request, CreerBesoinFormation $action)
    {
        $this->authorize('create', BesoinFormation::class);

        $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Besoin créé.']);
        }

        return redirect()->route('formation.besoins.index')
            ->with('success', 'Besoin créé.');
    }

    public function valider(Request $request, BesoinFormation $besoin, ValiderBesoinFormation $action)
    {
        $this->authorize('valider', $besoin);

        try {
            $action->executer($besoin);
            event(new BesoinFormationValide($besoin->fresh()));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Besoin validé.']);
        }

        return back()->with('success', 'Besoin validé.');
    }

    public function destroy(BesoinFormation $besoin)
    {
        $this->authorize('delete', $besoin);
        $besoin->marquerSupprime();

        return redirect()->route('formation.besoins.index')
            ->with('success', 'Besoin supprimé.');
    }
}