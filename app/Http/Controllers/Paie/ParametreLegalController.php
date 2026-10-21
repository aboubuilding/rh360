<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Models\RegleAnciennete;
use App\Domain\Paie\Models\RegleCotisation;
use App\Domain\Paie\Models\RegleIrpp;
use App\Domain\Paie\Requests\StoreRegleAncienneteRequest;
use App\Domain\Paie\Requests\StoreRegleCotisationRequest;
use App\Domain\Paie\Requests\StoreRegleIrppRequest;
use App\Http\Controllers\Controller;

class ParametreLegalController extends Controller
{
    public function index()
    {
        $this->authorize('permission', 'paie.manage');

        $cotisations = RegleCotisation::orderByDesc('debut_effet')->get();
        $anciennete = RegleAnciennete::orderByDesc('debut_effet')->get();
        $irpp = RegleIrpp::orderByDesc('debut_effet')->get();

        $externes = \App\Domain\Paie\Models\ParametreLegalPaie::orderBy('cle')->orderByDesc('debut_effet')->get();

        return view('paie.parametres.index', compact('cotisations', 'anciennete', 'irpp', 'externes'));
    }

    // Paramètre externe daté : SMIG (arrêté) ou barème de quotité saisissable (décret).
    public function storeExterne(\Illuminate\Http\Request $request)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'cle' => ['required', 'in:smig,quotite_saisissable'],
            'valeur' => ['nullable', 'required_if:cle,smig', 'numeric', 'min:1'],
            'tranches' => ['nullable', 'required_if:cle,quotite_saisissable', 'string', 'max:2000'],
            'debut_effet' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:255'],
        ], [
            'valeur.required_if' => 'Le montant du SMIG est obligatoire.',
            'tranches.required_if' => 'Les tranches du barème sont obligatoires.',
            'debut_effet.required' => 'La date d\'effet est obligatoire.',
        ]);

        $json = null;
        if ($donnees['cle'] === 'quotite_saisissable') {
            // « plafond ; pourcentage » par ligne ; dernière tranche sans plafond.
            $json = [];
            foreach (preg_split('/\R/', trim($donnees['tranches'])) as $ligne) {
                [$plafond, $pourcentage] = array_map('trim', explode(';', $ligne) + [1 => '']);
                $pourcentage = (float) str_replace(',', '.', $pourcentage);
                if ($pourcentage <= 0 || $pourcentage > 100) {
                    return back()->withInput()->withErrors(['tranches' => "Pourcentage invalide sur la ligne « {$ligne} »."]);
                }
                $json[] = [$plafond === '' ? null : (float) str_replace([' ', ','], ['', '.'], $plafond), round($pourcentage / 100, 6)];
            }
            if (end($json)[0] !== null) {
                return back()->withInput()->withErrors(['tranches' => 'La dernière tranche n\'a pas de plafond : commencez-la par « ; ».']);
            }
        }

        \App\Domain\Paie\Models\ParametreLegalPaie::updateOrCreate(
            ['entreprise_id' => auth()->user()->entreprise_id, 'cle' => $donnees['cle'], 'debut_effet' => $donnees['debut_effet']],
            ['valeur' => $donnees['valeur'] ?? null, 'valeur_json' => $json, 'commentaire' => $donnees['commentaire'] ?? null],
        );

        return redirect()->route('paie.parametres.index')->with('success', 'Paramètre enregistré.');
    }

    // --- Cotisations ---

    public function storeCotisation(StoreRegleCotisationRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleCotisation::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle de cotisation créée.']);
        }
        return back()->with('success', 'Règle de cotisation créée.');
    }

    public function updateCotisation(StoreRegleCotisationRequest $request, RegleCotisation $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }
        return back()->with('success', 'Règle mise à jour.');
    }

    public function destroyCotisation(RegleCotisation $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Règle supprimée.']);
        }
        return back()->with('success', 'Règle supprimée.');
    }

    // --- Ancienneté ---

    public function storeAnciennete(StoreRegleAncienneteRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleAnciennete::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle d\'ancienneté créée.']);
        }
        return back()->with('success', 'Règle d\'ancienneté créée.');
    }

    public function updateAnciennete(StoreRegleAncienneteRequest $request, RegleAnciennete $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Règle mise à jour.']);
        }
        return back()->with('success', 'Règle mise à jour.');
    }

    // --- IRPP ---

    public function storeIrpp(StoreRegleIrppRequest $request)
    {
        $this->authorize('permission', 'paie.manage');

        RegleIrpp::create(array_merge($request->validated(), [
            'entreprise_id' => auth()->user()->entreprise_id,
            'etat' => 1,
        ]));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Barème IRPP créé.']);
        }
        return back()->with('success', 'Barème IRPP créé.');
    }

    public function updateIrpp(StoreRegleIrppRequest $request, RegleIrpp $regle)
    {
        $this->authorize('permission', 'paie.manage');
        $regle->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Barème mis à jour.']);
        }
        return back()->with('success', 'Barème mis à jour.');
    }
}