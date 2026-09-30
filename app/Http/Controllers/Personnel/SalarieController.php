<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Actions\CreerSalarie;
use App\Domain\Personnel\Actions\ModifierSalarie;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Requests\StoreSalarieRequest;
use App\Domain\Personnel\Requests\UpdateSalarieRequest;
use App\Domain\Personnel\Services\CalculateurCompletude;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SalarieController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Salarie::class);

        $salaries = Salarie::query()
            ->with(['affectations.structure', 'affectations.poste'])
            ->recherche($request->q)
            ->deStructure($request->structure_id ? (int) $request->structure_id : null)
            ->parSituation($request->situation)
            ->parCompletude($request->completude)
            ->orderBy('nom')->orderBy('prenoms')
            ->paginate(50)
            ->withQueryString();

        $structures = Structure::orderBy('nom')->get();

        return view('personnel.salaries.index', compact('salaries', 'structures'));
    }

    public function create()
    {
        $this->authorize('create', Salarie::class);
        $structures = Structure::orderBy('nom')->get();
        $postes = Poste::orderBy('intitule')->get();
        return view('personnel.salaries.create', compact('structures', 'postes'));
    }

    public function store(StoreSalarieRequest $request, CreerSalarie $action)
    {
        $this->authorize('create', Salarie::class);

        // Les champs sensibles non autorisés sont ignorés, même s'ils sont envoyés (CDC §6)
        $donnees = Arr::except($request->validated(), Salarie::champsSensiblesInterdits($request->user()));

        // Gestion de la photo
        if ($request->hasFile('photo')) {
            $donnees['chemin_photo'] = $request->file('photo')->store('salaries/photos', 'local');
        }

        $salarie = $action->executer($donnees);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Salarié créé.',
                'redirect' => route('personnel.salaries.show', $salarie),
            ]);
        }

        return redirect()->route('personnel.salaries.show', $salarie)
            ->with('success', 'Salarié créé avec succès.');
    }

    public function show(Salarie $salarie)
    {
        $this->authorize('view', $salarie);

        $salarie->load([
            'affectations.structure', 'affectations.poste',
            'membresFoyer', 'documents',
        ]);

        $completude = app(CalculateurCompletude::class);

        return view('personnel.salaries.show', [
            'salarie' => $salarie,
            'champsManquants' => $completude->champsManquants($salarie),
        ]);
    }

    public function edit(Salarie $salarie)
    {
        $this->authorize('update', $salarie);
        $structures = Structure::orderBy('nom')->get();
        $postes = Poste::orderBy('intitule')->get();
        return view('personnel.salaries.edit', compact('salarie', 'structures', 'postes'));
    }

    public function update(UpdateSalarieRequest $request, Salarie $salarie, ModifierSalarie $action)
    {
        $this->authorize('update', $salarie);

        // Les champs sensibles non autorisés sont ignorés, même s'ils sont envoyés (CDC §6)
        $donnees = Arr::except($request->validated(), Salarie::champsSensiblesInterdits($request->user()));

        if ($request->hasFile('photo')) {
            if ($salarie->chemin_photo) {
                Storage::disk('local')->delete($salarie->chemin_photo);
            }
            $donnees['chemin_photo'] = $request->file('photo')->store('salaries/photos', 'local');
        }

        $action->executer($salarie, $donnees);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fiche mise à jour.']);
        }

        return redirect()->route('personnel.salaries.show', $salarie)
            ->with('success', 'Fiche mise à jour.');
    }

    public function photo(Salarie $salarie)
    {
        $this->authorize('view', $salarie);

        if (! $salarie->chemin_photo || ! Storage::disk('local')->exists($salarie->chemin_photo)) {
            abort(404);
        }

        return Storage::disk('local')->response($salarie->chemin_photo);
    }

    public function destroy(Salarie $salarie)
    {
        $this->authorize('delete', $salarie);
        $salarie->marquerSupprime();

        return redirect()->route('personnel.salaries.index')
            ->with('success', 'Salarié supprimé.');
    }
}