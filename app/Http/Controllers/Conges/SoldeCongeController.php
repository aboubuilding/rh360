<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Actions\AjusterSoldeConge;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Requests\AjusterSoldeCongeRequest;
use App\Domain\Conges\Services\CalculateurSolde;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SoldeCongeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SoldeConge::class);

        $annee = (int) $request->get('annee', now()->year);

        $soldes = SoldeConge::query()
            ->with(['salarie', 'typeConge'])
            ->pourAnnee($annee)
            ->when($request->filled('q'), fn ($q) => $q->whereHas('salarie', function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->q}%")
                  ->orWhere('prenoms', 'like', "%{$request->q}%")
                  ->orWhere('matricule', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('type_conge_id'), fn ($q) => $q->where('type_conge_id', $request->type_conge_id))
            ->orderBy('salarie_id')->orderBy('type_conge_id')
            ->paginate(50)
            ->withQueryString();

        return view('conges.soldes.index', [
            'soldes' => $soldes,
            'annee' => $annee,
            'types' => TypeConge::orderBy('nom')->get(),
        ]);
    }

    public function pourSalarie(Salarie $salarie)
    {
        $this->authorize('viewAny', SoldeConge::class);

        $annee = (int) request('annee', now()->year);
        $calculateur = app(CalculateurSolde::class);

        // Obtenir ou créer les soldes pour tous les types actifs
        $types = TypeConge::where('actif', true)
            ->where(function ($q) {
                $q->where('droit_annuel', '>', 0)
                  ->orWhereNotNull('droit_annuel');
            })
            ->orderBy('nom')
            ->get();

        $soldes = [];
        foreach ($types as $type) {
            if ((float) $type->droit_annuel === 0.0) continue;
            $soldes[] = $calculateur->obtenir($salarie, $type, $annee);
        }

        return view('conges.soldes.pour-salarie', [
            'salarie' => $salarie,
            'soldes' => collect($soldes),
            'annee' => $annee,
        ]);
    }

    public function ajuster(AjusterSoldeCongeRequest $request, SoldeConge $solde, AjusterSoldeConge $action)
    {
        $this->authorize('ajuster', $solde);

        $action->executer($solde, (float) $request->ajustement, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solde ajusté.']);
        }
        return back()->with('success', 'Solde ajusté.');
    }

    public function resynchroniser(Request $request, SoldeConge $solde, CalculateurSolde $calculateur)
    {
        $this->authorize('ajuster', $solde);

        $calculateur->resynchroniser($solde);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solde resynchronisé.']);
        }
        return back()->with('success', 'Solde resynchronisé.');
    }
}