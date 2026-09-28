<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Paie\Actions\CreerModelePaie;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Requests\StoreModelePaieRequest;
use App\Http\Controllers\Controller;

class ModelePaieController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ModelePaie::class);

        $modeles = ModelePaie::with(['categorie', 'rubriques.rubrique'])
            ->orderBy('nom')
            ->paginate(50);

        return view('paie.modeles.index', compact('modeles'));
    }

    public function create()
    {
        $this->authorize('create', ModelePaie::class);

        return view('paie.modeles.create', [
            'categories' => CategorieClassification::orderBy('ordre')->get(),
            'rubriques' => RubriquePaie::actives()->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreModelePaieRequest $request, CreerModelePaie $action)
    {
        $this->authorize('create', ModelePaie::class);

        $donnees = $request->validated();
        $rubriques = $donnees['rubriques'] ?? [];
        unset($donnees['rubriques']);

        $modele = $action->executer($donnees, $rubriques);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Modèle créé.',
                'redirect' => route('paie.modeles.index'),
            ]);
        }

        return redirect()->route('paie.modeles.index')
            ->with('success', 'Modèle créé.');
    }

    public function edit(ModelePaie $modele)
    {
        $this->authorize('update', $modele);

        $modele->load('rubriques.rubrique');

        return view('paie.modeles.edit', [
            'modele' => $modele,
            'categories' => CategorieClassification::orderBy('ordre')->get(),
            'rubriques' => RubriquePaie::actives()->orderBy('nom')->get(),
        ]);
    }

    public function update(StoreModelePaieRequest $request, ModelePaie $modele)
    {
        $this->authorize('update', $modele);

        $donnees = $request->validated();
        $rubriques = $donnees['rubriques'] ?? [];
        unset($donnees['rubriques']);

        \DB::transaction(function () use ($modele, $donnees, $rubriques) {
            $modele->update($donnees);

            $modele->rubriques()->delete();
            foreach ($rubriques as $ordre => $r) {
                $modele->rubriques()->create([
                    'entreprise_id' => $modele->entreprise_id,
                    'rubrique_id' => $r['rubrique_id'],
                    'montant_defaut' => $r['montant_defaut'] ?? 0,
                    'obligatoire' => $r['obligatoire'] ?? false,
                    'ordre' => $r['ordre'] ?? ($ordre * 10),
                    'actif' => true,
                    'etat' => 1,
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Modèle mis à jour.']);
        }

        return redirect()->route('paie.modeles.index')
            ->with('success', 'Modèle mis à jour.');
    }

    public function destroy(ModelePaie $modele)
    {
        $this->authorize('update', $modele);
        $modele->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Modèle supprimé.']);
        }

        return redirect()->route('paie.modeles.index')
            ->with('success', 'Modèle supprimé.');
    }
}