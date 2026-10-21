<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Models\JourFerie;
use App\Domain\Conges\Services\CalendrierJoursFeries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Calendrier des jours fériés de l'entreprise.
 */
class JourFerieController extends Controller
{
    public function __construct(private CalendrierJoursFeries $calendrier) {}

    public function index(Request $request)
    {
        $this->authorize('permission', 'conges.view');

        $annee = (int) $request->get('annee', today()->year);

        return view('conges.jours-feries.index', [
            'annee' => $annee,
            'jours' => JourFerie::whereYear('date', $annee)->orderBy('date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('permission', 'conges.manage');

        $entrepriseId = auth()->user()->entreprise_id;
        $donnees = $request->validate([
            'date' => ['required', 'date', Rule::unique('jours_feries', 'date')->where('entreprise_id', $entrepriseId)],
            'libelle' => ['required', 'string', 'max:160'],
        ], [
            'date.required' => 'La date est obligatoire.',
            'date.unique' => 'Cette date figure déjà dans le calendrier.',
            'libelle.required' => 'Le libellé est obligatoire.',
        ]);

        JourFerie::create($donnees + ['entreprise_id' => $entrepriseId, 'origine' => 'saisi', 'cree_par' => auth()->id()]);
        $this->calendrier->oublier($entrepriseId);

        return $this->repondre($request, 'Jour férié ajouté.', (int) substr($donnees['date'], 0, 4));
    }

    public function proposer(Request $request)
    {
        $this->authorize('permission', 'conges.manage');

        $annee = (int) $request->validate(['annee' => ['required', 'integer', 'min:2000', 'max:2100']])['annee'];
        $ajoutes = $this->calendrier->proposer($annee, auth()->user()->entreprise_id);

        return $this->repondre($request, "{$ajoutes} jour(s) férié(s) ajouté(s) pour {$annee}. Vérifiez-les et ajoutez les fêtes dont la date est annoncée chaque année.", $annee);
    }

    public function destroy(Request $request, JourFerie $jourFerie)
    {
        $this->authorize('permission', 'conges.manage');

        $annee = $jourFerie->date->year;
        $jourFerie->delete();
        $this->calendrier->oublier(auth()->user()->entreprise_id);

        return $this->repondre($request, 'Jour férié retiré du calendrier.', $annee);
    }

    private function repondre(Request $request, string $message, int $annee)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('conges.jours-feries.index', ['annee' => $annee])->with('success', $message);
    }
}
