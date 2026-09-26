<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Actions\AnnulerContrat;
use App\Domain\Contrats\Actions\CreerAvenant;
use App\Domain\Contrats\Actions\CreerContrat;
use App\Domain\Contrats\Actions\RetournerBrouillon;
use App\Domain\Contrats\Actions\SignerContrat;
use App\Domain\Contrats\Actions\SoumettreContrat;
use App\Domain\Contrats\Actions\ValiderContrat;
use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\ParametreContrat;
use App\Domain\Contrats\Requests\AnnulerContratRequest;
use App\Domain\Contrats\Requests\RetournerBrouillonRequest;
use App\Domain\Contrats\Requests\SignerContratRequest;
use App\Domain\Contrats\Requests\StoreAvenantRequest;
use App\Domain\Contrats\Requests\StoreContratRequest;
use App\Domain\Contrats\Requests\UpdateContratRequest;
use App\Domain\Contrats\Requests\ValiderContratRequest;
use App\Domain\Contrats\Services\CalculateurFinEssai;
use App\Domain\Contrats\Services\GestionnairePieces;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContratController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Contrat::class);

        $contrats = Contrat::query()
            ->with(['salarie', 'poste'])
            ->recherche($request->q)
            ->deType($request->type_contrat)
            ->deStatut($request->statut)
            ->periodeEffet($request->du, $request->au)
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        return view('contrats.contrats.index', [
            'contrats' => $contrats,
            'types' => TypeContrat::options(),
            'statuts' => StatutContrat::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Contrat::class);

        $salarie = $request->filled('salarie_id')
            ? Salarie::findOrFail($request->salarie_id)
            : null;

        return view('contrats.contrats.create', [
            'salarie' => $salarie,
            'salaries' => Salarie::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::orderBy('ordre')->get(),
            'types' => TypeContrat::options(),
        ]);
    }

    public function store(StoreContratRequest $request, CreerContrat $action)
    {
        $this->authorize('create', Contrat::class);

        $contrat = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Contrat créé.',
                'redirect' => route('contrats.contrats.show', $contrat),
            ]);
        }

        return redirect()->route('contrats.contrats.show', $contrat)
            ->with('success', 'Contrat créé.');
    }

    public function show(Contrat $contrat)
    {
        $this->authorize('view', $contrat);

        $contrat->load([
            'salarie', 'poste', 'positionClassification',
            'pieces.creePar', 'historique.utilisateur',
            'evenementsEssai', 'alertes', 'parent', 'avenants',
        ]);

        $calculEssai = app(CalculateurFinEssai::class)->calculer($contrat);

        return view('contrats.contrats.show', compact('contrat', 'calculEssai'));
    }

    public function edit(Contrat $contrat)
    {
        $this->authorize('update', $contrat);

        return view('contrats.contrats.edit', [
            'contrat' => $contrat,
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::orderBy('ordre')->get(),
            'types' => TypeContrat::options(),
        ]);
    }

    public function update(UpdateContratRequest $request, Contrat $contrat)
    {
        $this->authorize('update', $contrat);

        $contrat->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contrat mis à jour.']);
        }

        return redirect()->route('contrats.contrats.show', $contrat)
            ->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Contrat $contrat)
    {
        $this->authorize('delete', $contrat);

        $contrat->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Contrat supprimé.']);
        }

        return redirect()->route('contrats.contrats.index')
            ->with('success', 'Contrat supprimé.');
    }

    // --- Avenants ---

    public function createAvenant(Contrat $contrat)
    {
        $this->authorize('creerAvenant', $contrat);

        return view('contrats.contrats.create-avenant', [
            'parent' => $contrat,
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::orderBy('ordre')->get(),
            'types' => TypeContrat::options(),
        ]);
    }

    public function storeAvenant(StoreAvenantRequest $request, Contrat $contrat, CreerAvenant $action)
    {
        $this->authorize('creerAvenant', $contrat);

        $avenant = $action->executer($contrat, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Avenant créé.',
                'redirect' => route('contrats.contrats.show', $avenant),
            ]);
        }

        return redirect()->route('contrats.contrats.show', $avenant)
            ->with('success', 'Avenant créé.');
    }

    // --- Transitions ---

    public function soumettre(Contrat $contrat, SoumettreContrat $action)
    {
        $this->authorize('soumettre', $contrat);

        $action->executer($contrat);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Contrat soumis pour validation.']);
        }

        return back()->with('success', 'Contrat soumis.');
    }

    public function valider(ValiderContratRequest $request, Contrat $contrat, ValiderContrat $action)
    {
        $this->authorize('valider', $contrat);

        $action->executer($contrat, $request->note_derogation);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contrat validé.']);
        }

        return back()->with('success', 'Contrat validé.');
    }

    public function retournerBrouillon(RetournerBrouillonRequest $request, Contrat $contrat, RetournerBrouillon $action)
    {
        $this->authorize('retournerBrouillon', $contrat);

        $action->executer($contrat, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contrat retourné au brouillon.']);
        }

        return back()->with('success', 'Contrat retourné au brouillon.');
    }

    public function annuler(AnnulerContratRequest $request, Contrat $contrat, AnnulerContrat $action)
    {
        $this->authorize('annuler', $contrat);

        $action->executer($contrat, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contrat annulé.']);
        }

        return back()->with('success', 'Contrat annulé.');
    }

    public function signer(SignerContratRequest $request, Contrat $contrat, SignerContrat $action)
    {
        $this->authorize('signer', $contrat);

        // Enregistrer la pièce signée avant la transition (si fournie)
        if ($request->hasFile('piece')) {
            app(GestionnairePieces::class)->enregistrer(
                $contrat,
                $request->file('piece'),
                ObjetPieceContrat::CONTRAT_SIGNE,
                'Document signé réf. ' . $request->reference_signee,
            );
        }

        $action->executer($contrat, $request->date_signature, $request->reference_signee);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Signature référencée.']);
        }

        return back()->with('success', 'Signature référencée.');
    }
}