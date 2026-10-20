<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Interim\Enums\StatutAgrement;
use App\Domain\Interim\Models\EntrepriseInterim;
use App\Domain\Interim\Requests\StoreEntrepriseInterimRequest;
use App\Domain\Interim\Requests\StoreFactureInterimRequest;
use App\Domain\Interim\Services\MissionInterimService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Entreprises de travail temporaire agréées (RG-TT-01) et leurs factures (RG-TT-04).
 */
class AgenceInterimController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', EntrepriseInterim::class);

        return view('carriere.interims.agences.index', [
            'agences' => EntrepriseInterim::withCount('missions')->with(['factures' => fn ($q) => $q->latest('date_facture')])
                ->orderBy('raison_sociale')->get(),
            'statuts' => StatutAgrement::options(),
        ]);
    }

    public function store(StoreEntrepriseInterimRequest $request)
    {
        $this->authorize('create', EntrepriseInterim::class);

        EntrepriseInterim::create($request->validated() + ['cree_par' => auth()->id()]);

        return $this->repondre($request, 'Agence enregistrée.');
    }

    public function update(StoreEntrepriseInterimRequest $request, EntrepriseInterim $agence)
    {
        $this->authorize('gerer', $agence);

        $agence->update($request->validated());

        return $this->repondre($request, 'Agence mise à jour.');
    }

    public function facture(StoreFactureInterimRequest $request, EntrepriseInterim $agence, MissionInterimService $service)
    {
        $this->authorize('gerer', $agence);

        $service->enregistrerFacture($agence, $request->safe()->except('piece'), $request->file('piece'));

        return $this->repondre($request, 'Facture enregistrée.');
    }

    private function repondre(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('carriere.interims.agences.index')->with('success', $message);
    }
}
