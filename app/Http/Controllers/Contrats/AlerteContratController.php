<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Actions\CloturerAlerte;
use App\Domain\Contrats\Models\AlerteContrat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlerteContratController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AlerteContrat::class);

        $alertes = AlerteContrat::query()
            ->with(['contrat.salarie'])
            ->when($request->filled('en_cours'), fn ($q) => $q->where('en_cours', $request->en_cours === '1'))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('intitule', 'like', "%{$request->q}%")
                  ->orWhereHas('contrat.salarie', function ($q) use ($request) {
                      $q->where('nom', 'like', "%{$request->q}%")
                        ->orWhere('prenoms', 'like', "%{$request->q}%");
                  });
            }))
            ->orderBy('date_echeance')
            ->paginate(50)
            ->withQueryString();

        return view('contrats.alertes.index', compact('alertes'));
    }

    public function cloturer(Request $request, AlerteContrat $alerte, CloturerAlerte $action)
    {
        $this->authorize('cloturer', $alerte);

        $donnees = $request->validate([
            'note_cloture' => ['nullable', 'string', 'max:2000'],
        ], [
            'note_cloture.max' => 'La note ne doit pas dépasser 2000 caractères.',
        ]);

        $action->executer($alerte, $donnees['note_cloture'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Alerte clôturée.']);
        }

        return back()->with('success', 'Alerte clôturée.');
    }
}