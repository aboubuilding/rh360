<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Actions\AjouterHeuresSupplementaires;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Requests\StoreHeureSupplementaireRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HeureSupplementaireController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', HeureSupplementaire::class);

        $actes = HeureSupplementaire::query()
            ->with(['salarie', 'periodePaiement'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->q}%")
                  ->orWhereHas('salarie', function ($q) use ($request) {
                      $q->where('nom', 'like', "%{$request->q}%")
                        ->orWhere('prenoms', 'like', "%{$request->q}%")
                        ->orWhere('matricule', 'like', "%{$request->q}%");
                  });
            }))
            ->when($request->filled('periode_id'), fn ($q) => $q->where('periode_paiement_id', $request->periode_id))
            ->orderByDesc('debut_travail')
            ->paginate(50)
            ->withQueryString();

        return view('paie.heures-supp.index', [
            'actes' => $actes,
            'periodes' => PeriodePaie::recentes()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('permission', 'paie.manage');

        return view('paie.heures-supp.create', [
            'salarie' => $request->filled('salarie_id') ? Salarie::findOrFail($request->salarie_id) : null,
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'periodes' => PeriodePaie::recentes()->get(),
        ]);
    }

    public function store(StoreHeureSupplementaireRequest $request, AjouterHeuresSupplementaires $action)
    {
        $this->authorize('permission', 'paie.manage');

        $acte = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acte d\'heures supplémentaires créé.',
                'redirect' => route('paie.heures-supp.index'),
            ]);
        }

        return redirect()->route('paie.heures-supp.index')
            ->with('success', 'Acte d\'heures supplémentaires créé.');
    }

    public function show(HeureSupplementaire $heuresSupp)
    {
        $this->authorize('viewAny', HeureSupplementaire::class);

        $heuresSupp->load(['salarie', 'periodePaiement', 'periodeOrigine', 'creePar']);

        $detail = app(\App\Domain\Paie\Services\CalculateurHeuresSupp::class)->detailActe($heuresSupp);

        return view('paie.heures-supp.show', [
            'acte' => $heuresSupp,
            'detail' => $detail,
        ]);
    }

    public function destroy(HeureSupplementaire $heuresSupp)
    {
        $this->authorize('permission', 'paie.manage');

        // On ne peut supprimer que si la période n'est pas validée
        if ($heuresSupp->periodePaiement?->estFigee()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Période figée : suppression impossible.'], 422);
            }
            return back()->with('error', 'Période figée : suppression impossible.');
        }

        $heuresSupp->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Acte supprimé.']);
        }

        return redirect()->route('paie.heures-supp.index')
            ->with('success', 'Acte supprimé.');
    }
}