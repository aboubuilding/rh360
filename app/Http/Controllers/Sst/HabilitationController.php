<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Actions\EnregistrerHabilitation;
use App\Domain\Sst\Actions\RenouvelerHabilitation;
use App\Domain\Sst\Actions\RevoquerHabilitation;
use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Requests\RenouvelerHabilitationRequest;
use App\Domain\Sst\Requests\StoreHabilitationRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HabilitationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Habilitation::class);

        $habilitations = Habilitation::query()
            ->with(['salarie', 'risque'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('intitule', 'like', "%{$request->q}%")
                  ->orWhere('categorie', 'like', "%{$request->q}%")
                  ->orWhereHas('salarie', function ($q) use ($request) {
                      $q->where('nom', 'like', "%{$request->q}%")
                        ->orWhere('prenoms', 'like', "%{$request->q}%");
                  });
            }))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('echeance'), function ($q) use ($request) {
                if ($request->echeance === 'proche') {
                    $q->echeanceProche(60);
                } elseif ($request->echeance === 'expirees') {
                    $q->expirees();
                }
            })
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        return view('sst.habilitations.index', [
            'habilitations' => $habilitations,
            'statuts' => StatutHabilitation::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Habilitation::class);

        return view('sst.habilitations.create', [
            'salarie' => $request->filled('salarie_id') ? Salarie::findOrFail($request->salarie_id) : null,
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'risques' => Risque::where('statut', 'active')->orderBy('intitule')->get(),
        ]);
    }

    public function store(StoreHabilitationRequest $request, EnregistrerHabilitation $action)
    {
        $this->authorize('create', Habilitation::class);

        $habilitation = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Habilitation enregistrée.',
                'redirect' => route('sst.habilitations.show', $habilitation),
            ]);
        }

        return redirect()->route('sst.habilitations.show', $habilitation)
            ->with('success', 'Habilitation enregistrée.');
    }

    public function show(Habilitation $habilitation)
    {
        $this->authorize('view', $habilitation);

        $habilitation->load([
            'salarie', 'risque', 'habilitationOrigine',
            'renouvellements', 'creePar', 'modifiePar',
        ]);

        return view('sst.habilitations.show', compact('habilitation'));
    }

    public function renouveler(RenouvelerHabilitationRequest $request, Habilitation $habilitation, RenouvelerHabilitation $action)
    {
        $this->authorize('renouveler', $habilitation);

        $nouvelle = $action->executer($habilitation, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Habilitation renouvelée.',
                'redirect' => route('sst.habilitations.show', $nouvelle),
            ]);
        }

        return redirect()->route('sst.habilitations.show', $nouvelle)
            ->with('success', 'Habilitation renouvelée.');
    }

    public function revoquer(Request $request, Habilitation $habilitation, RevoquerHabilitation $action)
    {
        $this->authorize('revoquer', $habilitation);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $action->executer($habilitation, $donnees['motif']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Habilitation révoquée.']);
        }
        return back()->with('success', 'Habilitation révoquée.');
    }

    public function destroy(Habilitation $habilitation)
    {
        $this->authorize('delete', $habilitation);
        $habilitation->marquerSupprime();

        return redirect()->route('sst.habilitations.index')
            ->with('success', 'Habilitation supprimée.');
    }
}