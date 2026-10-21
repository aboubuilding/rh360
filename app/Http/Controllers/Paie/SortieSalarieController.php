<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\SortieSalarie;
use App\Domain\Paie\Services\CalculateurSoldeToutCompte;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sorties des salariés : éléments du solde de tout compte, portés sur le bulletin du mois de la
 * cessation du service.
 */
class SortieSalarieController extends Controller
{
    public function __construct(private CalculateurSoldeToutCompte $solde) {}

    public function index(Request $request)
    {
        $this->authorize('permission', 'paie.view');

        $sortie = $request->filled('sortie') ? SortieSalarie::with('salarie')->find($request->sortie) : null;

        return view('paie.sorties.index', [
            'sorties' => SortieSalarie::with('salarie')->orderByDesc('date_cessation')->limit(50)->get(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'sortie' => $sortie,
            'calcul' => $sortie ? $this->solde->calculer($sortie) : null,
            'periode' => $sortie ? PeriodePaie::where('annee', $sortie->date_cessation->year)->where('mois', $sortie->date_cessation->month)->first() : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'salarie_id' => ['required', Rule::exists('salaries', 'id')->where('entreprise_id', auth()->user()->entreprise_id)],
            'date_cessation' => ['required', 'date', Rule::unique('sorties_salaries')->where('salarie_id', $request->input('salarie_id'))],
            'motif' => ['required', Rule::in(array_keys(SortieSalarie::MOTIFS))],
            'preavis' => ['required', Rule::in(array_keys(SortieSalarie::PREAVIS))],
            'preavis_mois' => ['nullable', 'numeric', 'min:0', 'max:6'],
            'faute' => ['nullable', Rule::in(array_keys(SortieSalarie::FAUTES))],
            'montant_convenu' => ['nullable', 'numeric', 'min:0', 'required_if:motif,rupture_conventionnelle'],
            'heures_recherche' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'exception_fin_contrat' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ], [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'date_cessation.required' => 'La date de cessation du service est obligatoire.',
            'date_cessation.unique' => 'Une sortie est déjà enregistrée à cette date pour ce salarié.',
            'montant_convenu.required_if' => 'Le montant convenu de la rupture est obligatoire.',
        ]);

        // Le bulletin de sortie se calcule sur une période encore ouverte.
        $date = \Carbon\Carbon::parse($donnees['date_cessation']);
        $periode = PeriodePaie::where('annee', $date->year)->where('mois', $date->month)->first();
        if ($periode?->estFigee()) {
            return back()->withInput()->with('error', "La période {$periode->libelle} est validée : la sortie ne peut plus y être payée.");
        }

        $sortie = SortieSalarie::create($donnees + [
            'entreprise_id' => auth()->user()->entreprise_id,
            'exception_fin_contrat' => $request->boolean('exception_fin_contrat'),
            'cree_par' => auth()->id(),
        ]);

        return redirect()->route('paie.sorties.index', ['sortie' => $sortie->id])
            ->with('success', 'Sortie enregistrée : générez la paie du mois pour produire le bulletin de sortie.');
    }

    public function destroy(SortieSalarie $sortie)
    {
        $this->authorize('permission', 'paie.manage');

        $periode = PeriodePaie::where('annee', $sortie->date_cessation->year)->where('mois', $sortie->date_cessation->month)->first();
        if ($periode?->estFigee()) {
            return back()->with('error', 'Le bulletin de sortie est validé : la sortie ne peut plus être retirée.');
        }
        $sortie->delete();

        return redirect()->route('paie.sorties.index')->with('success', 'Sortie retirée.');
    }
}
