<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Actions\AnnulerOperationEpi;
use App\Domain\Sst\Actions\EnregistrerDotationEpi;
use App\Domain\Sst\Actions\EnregistrerOperationEpi;
use App\Domain\Sst\Enums\CategorieEpi;
use App\Domain\Sst\Enums\NatureOperationEpi;
use App\Domain\Sst\Enums\StatutDotationEpi;
use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\OperationEpi;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Requests\StoreDotationEpiRequest;
use App\Domain\Sst\Requests\StoreOperationEpiRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DotationEpiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DotationEpi::class);

        $dotations = DotationEpi::query()
            ->with(['salarie', 'risque'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('intitule', 'like', "%{$request->q}%")
                  ->orWhere('numero_serie', 'like', "%{$request->q}%")
                  ->orWhereHas('salarie', function ($q) use ($request) {
                      $q->where('nom', 'like', "%{$request->q}%")
                        ->orWhere('prenoms', 'like', "%{$request->q}%");
                  });
            }))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->categorie))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->orderByDesc('date_remise')
            ->paginate(50)
            ->withQueryString();

        return view('sst.epi.index', [
            'dotations' => $dotations,
            'categories' => CategorieEpi::options(),
            'statuts' => StatutDotationEpi::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', DotationEpi::class);

        return view('sst.epi.create', [
            'categories' => CategorieEpi::options(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'risques' => Risque::where('statut', 'active')->orderBy('intitule')->get(),
        ]);
    }

    public function store(StoreDotationEpiRequest $request, EnregistrerDotationEpi $action)
    {
        $this->authorize('create', DotationEpi::class);

        $dotation = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Dotation enregistrée.',
                'redirect' => route('sst.epi.show', $dotation),
            ]);
        }

        return redirect()->route('sst.epi.show', $dotation)
            ->with('success', 'Dotation enregistrée.');
    }

    public function show(DotationEpi $epi)
    {
        $this->authorize('view', $epi);

        $epi->load(['salarie', 'risque', 'operations.creePar', 'creePar']);

        return view('sst.epi.show', [
            'dotation' => $epi,
            'natures' => NatureOperationEpi::options(),
        ]);
    }

    public function ajouterOperation(StoreOperationEpiRequest $request, DotationEpi $epi, EnregistrerOperationEpi $action)
    {
        $this->authorize('update', $epi);

        $action->executer($epi, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Opération enregistrée.']);
        }
        return back()->with('success', 'Opération enregistrée.');
    }

    public function annulerOperation(Request $request, DotationEpi $epi, OperationEpi $operation, AnnulerOperationEpi $action)
    {
        $this->authorize('annulerOperation', $epi);

        if ($operation->dotation_id !== $epi->id) {
            abort(404);
        }

        $donnees = $request->validate([
            'motif_annulation' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $action->executer($operation, $donnees['motif_annulation']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Opération annulée.']);
        }
        return back()->with('success', 'Opération annulée.');
    }

    public function destroy(DotationEpi $epi)
    {
        $this->authorize('delete', $epi);
        $epi->marquerSupprime();

        return redirect()->route('sst.epi.index')
            ->with('success', 'Dotation supprimée.');
    }
}