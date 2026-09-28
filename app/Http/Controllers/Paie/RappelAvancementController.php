<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Actions\GenererRappelsAvancement;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RappelAvancement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RappelAvancementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'paie.view');

        $rappels = RappelAvancement::query()
            ->with(['salarie', 'mouvement', 'periodeGeneration'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('salarie', function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->q}%")
                  ->orWhere('prenoms', 'like', "%{$request->q}%")
                  ->orWhere('matricule', 'like', "%{$request->q}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('paie.rappels.index', compact('rappels'));
    }

    public function generate(Request $request, GenererRappelsAvancement $action)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'periode_id' => ['required', 'exists:periodes_paie,id'],
        ]);

        $periode = PeriodePaie::findOrFail($donnees['periode_id']);

        if ($periode->estFigee()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Période figée.'], 422);
            }
            return back()->with('error', 'Période figée.');
        }

        $count = $action->executer($periode);

        $message = "{$count} rappel(s) généré(s).";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}