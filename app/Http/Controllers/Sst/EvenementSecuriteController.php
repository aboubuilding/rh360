<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Sst\Actions\AjouterActionSecurite;
use App\Domain\Sst\Actions\CloturerEvenementSecurite;
use App\Domain\Sst\Actions\DeclarerEvenementSecurite;
use App\Domain\Sst\Enums\StatutEvenementSecurite;
use App\Domain\Sst\Enums\TypeEvenementSecurite;
use App\Domain\Sst\Events\EvenementSecuriteCloture;
use App\Domain\Sst\Models\EvenementSecurite;
use App\Domain\Sst\Requests\CloturerEvenementSecuriteRequest;
use App\Domain\Sst\Requests\StoreActionSecuriteRequest;
use App\Domain\Sst\Requests\StoreEvenementSecuriteRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EvenementSecuriteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EvenementSecurite::class);

        $evenements = EvenementSecurite::query()
            ->with(['participants', 'creePar'])
            ->recherche($request->q)
            ->deType($request->type_evenement)
            ->deStatut($request->statut)
            ->periode($request->du, $request->au)
            ->when($request->filled('en_cours'), fn ($q) => $q->enCours())
            ->orderByDesc('date_survenance')
            ->paginate(50)
            ->withQueryString();

        return view('sst.evenements.index', [
            'evenements' => $evenements,
            'types' => TypeEvenementSecurite::options(),
            'statuts' => StatutEvenementSecurite::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', EvenementSecurite::class);

        return view('sst.evenements.create', [
            'types' => TypeEvenementSecurite::options(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreEvenementSecuriteRequest $request, DeclarerEvenementSecurite $action)
    {
        $this->authorize('create', EvenementSecurite::class);

        $evenement = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Événement déclaré.',
                'redirect' => route('sst.evenements.show', $evenement),
            ]);
        }

        return redirect()->route('sst.evenements.show', $evenement)
            ->with('success', 'Événement déclaré.');
    }

    public function show(EvenementSecurite $evenement)
    {
        $this->authorize('view', $evenement);

        $evenement->load([
            'participants', 'actions.responsable',
            'piecesJointes', 'creePar', 'modifiePar',
        ]);

        return view('sst.evenements.show', compact('evenement'));
    }

    public function cloturer(CloturerEvenementSecuriteRequest $request, EvenementSecurite $evenement, CloturerEvenementSecurite $action)
    {
        $this->authorize('cloturer', $evenement);

        try {
            $action->executer($evenement, $request->synthese_cloture);
            event(new EvenementSecuriteCloture($evenement->fresh()));
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Événement clôturé.']);
        }
        return back()->with('success', 'Événement clôturé.');
    }

    public function annuler(Request $request, EvenementSecurite $evenement)
    {
        $this->authorize('annuler', $evenement);

        $donnees = $request->validate([
            'motif_annulation' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'motif_annulation.required' => 'Le motif d\'annulation est obligatoire.',
        ]);

        $evenement->update([
            'statut' => StatutEvenementSecurite::ANNULE->value,
            'motif_annulation' => $donnees['motif_annulation'],
            'revision' => $evenement->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Événement annulé.']);
        }
        return back()->with('success', 'Événement annulé.');
    }

    public function ajouterAction(StoreActionSecuriteRequest $request, EvenementSecurite $evenement, AjouterActionSecurite $action)
    {
        $this->authorize('gererActions', $evenement);

        $action->executer($evenement, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Action ajoutée.']);
        }
        return back()->with('success', 'Action ajoutée.');
    }

    public function destroy(EvenementSecurite $evenement)
    {
        $this->authorize('delete', $evenement);
        $evenement->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Événement supprimé.']);
        }

        return redirect()->route('sst.evenements.index')
            ->with('success', 'Événement supprimé.');
    }
}