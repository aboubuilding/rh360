<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Actions\AnnulerMouvement;
use App\Domain\Carriere\Actions\AppliquerMouvement;
use App\Domain\Carriere\Actions\CloturerMouvement;
use App\Domain\Carriere\Actions\ControlerMouvement;
use App\Domain\Carriere\Actions\CreerMouvement;
use App\Domain\Carriere\Actions\ModifierMouvement;
use App\Domain\Carriere\Actions\ProgrammerMouvement;
use App\Domain\Carriere\Actions\RejeterMouvement;
use App\Domain\Carriere\Actions\SoumettreMouvement;
use App\Domain\Carriere\Actions\ValiderMouvement;
use App\Domain\Carriere\Actions\VerifierMouvement;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Requests\AnnulerMouvementRequest;
use App\Domain\Carriere\Requests\ProgrammerMouvementRequest;
use App\Domain\Carriere\Requests\RejeterMouvementRequest;
use App\Domain\Carriere\Requests\StoreMouvementRequest;
use App\Domain\Carriere\Requests\SoumettreMouvementRequest;
use App\Domain\Carriere\Requests\UpdateMouvementRequest;
use App\Domain\Carriere\Requests\ValiderMouvementRequest;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MouvementCarriereController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MouvementCarriere::class);

        $vue = $request->get('vue', 'tous');

        $query = MouvementCarriere::query()
            ->with(['salarie', 'structureDepart', 'structureCible', 'posteDepart', 'posteCible']);

        if ($vue === 'a_traiter') {
            $query->whereIn('statut', [
                StatutMouvement::PROPOSE->value,
                StatutMouvement::A_VERIFIER->value,
                StatutMouvement::VERIFIE->value,
            ]);
        } elseif ($vue === 'programmes') {
            $query->where('statut', StatutMouvement::PROGRAMME->value);
        }

        $mouvements = $query
            ->recherche($request->q)
            ->deType($request->type_mouvement)
            ->deStatut($request->statut)
            ->periode($request->du, $request->au)
            ->orderByDesc('date_proposition')
            ->paginate(50)
            ->withQueryString();

        return view('carriere.mouvements.index', [
            'mouvements' => $mouvements,
            'vue' => $vue,
            'types' => TypeMouvement::options(),
            'statuts' => StatutMouvement::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', MouvementCarriere::class);

        $salarie = $request->filled('salarie_id')
            ? Salarie::findOrFail($request->salarie_id)
            : null;

        return view('carriere.mouvements.create', [
            'salarie' => $salarie,
            'salaries' => Salarie::orderBy('nom')->get(),
            'structures' => Structure::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::orderBy('ordre')->get(),
            'types' => TypeMouvement::options(),
        ]);
    }

    public function store(StoreMouvementRequest $request, CreerMouvement $action)
    {
        $this->authorize('create', MouvementCarriere::class);

        $mouvement = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Mouvement créé.',
                'redirect' => route('carriere.mouvements.show', $mouvement),
            ]);
        }

        return redirect()->route('carriere.mouvements.show', $mouvement)
            ->with('success', 'Mouvement créé.');
    }

    public function show(MouvementCarriere $mouvement)
    {
        $this->authorize('view', $mouvement);

        $mouvement->load([
            'salarie', 'structureDepart', 'structureCible',
            'posteDepart', 'posteCible',
            'positionDepart', 'positionCible',
            'instantane', 'creePar', 'controlePar', 'validePar',
        ]);

        return view('carriere.mouvements.show', compact('mouvement'));
    }

    public function edit(MouvementCarriere $mouvement)
    {
        $this->authorize('update', $mouvement);

        return view('carriere.mouvements.edit', [
            'mouvement' => $mouvement,
            'structures' => Structure::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::orderBy('ordre')->get(),
            'types' => TypeMouvement::options(),
        ]);
    }

    public function update(UpdateMouvementRequest $request, MouvementCarriere $mouvement, ModifierMouvement $action)
    {
        $this->authorize('update', $mouvement);

        $action->executer($mouvement, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement mis à jour.']);
        }

        return redirect()->route('carriere.mouvements.show', $mouvement)
            ->with('success', 'Mouvement mis à jour.');
    }

    public function destroy(MouvementCarriere $mouvement)
    {
        $this->authorize('delete', $mouvement);
        $mouvement->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Mouvement supprimé.']);
        }

        return redirect()->route('carriere.mouvements.index')
            ->with('success', 'Mouvement supprimé.');
    }

    // --- Transitions ---

    public function soumettre(SoumettreMouvementRequest $request, MouvementCarriere $mouvement, SoumettreMouvement $action)
    {
        $this->authorize('soumettre', $mouvement);

        if ($request->filled('date_eligibilite')) {
            $mouvement->update(['date_eligibilite' => $request->date_eligibilite]);
        }

        $action->executer($mouvement);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement soumis.']);
        }
        return back()->with('success', 'Mouvement soumis.');
    }

    public function controler(Request $request, MouvementCarriere $mouvement, ControlerMouvement $action)
    {
        $this->authorize('controler', $mouvement);

        $action->executer($mouvement, $request->input('observations'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement contrôlé.']);
        }
        return back()->with('success', 'Contrôle enregistré.');
    }

    public function verifier(ValiderMouvementRequest $request, MouvementCarriere $mouvement, VerifierMouvement $action)
    {
        $this->authorize('verifier', $mouvement);

        $action->executer($mouvement, $request->note_validation);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement vérifié.']);
        }
        return back()->with('success', 'Mouvement vérifié.');
    }

    public function valider(ValiderMouvementRequest $request, MouvementCarriere $mouvement, ValiderMouvement $action)
    {
        $this->authorize('valider', $mouvement);

        $action->executer($mouvement, $request->note_validation);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement validé.']);
        }
        return back()->with('success', 'Mouvement validé.');
    }

    public function rejeter(RejeterMouvementRequest $request, MouvementCarriere $mouvement, RejeterMouvement $action)
    {
        $this->authorize('rejeter', $mouvement);

        $action->executer($mouvement, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement rejeté.']);
        }
        return back()->with('success', 'Mouvement rejeté.');
    }

    public function programmer(ProgrammerMouvementRequest $request, MouvementCarriere $mouvement, ProgrammerMouvement $action)
    {
        $this->authorize('programmer', $mouvement);

        $action->executer($mouvement, $request->date_effet);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement programmé.']);
        }
        return back()->with('success', 'Mouvement programmé.');
    }

    public function appliquer(Request $request, MouvementCarriere $mouvement, AppliquerMouvement $action)
    {
        $this->authorize('programmer', $mouvement);

        if (! $mouvement->peutEtreApplique()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ce mouvement ne peut pas être appliqué maintenant.'], 422);
            }
            return back()->with('error', 'Ce mouvement ne peut pas être appliqué maintenant.');
        }

        $action->executer($mouvement);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement appliqué.']);
        }
        return back()->with('success', 'Mouvement appliqué.');
    }

    public function cloturer(Request $request, MouvementCarriere $mouvement, CloturerMouvement $action)
    {
        $this->authorize('cloturer', $mouvement);

        $action->executer($mouvement, $request->input('motif_cloture'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement clôturé.']);
        }
        return back()->with('success', 'Mouvement clôturé.');
    }

    public function annuler(AnnulerMouvementRequest $request, MouvementCarriere $mouvement, AnnulerMouvement $action)
    {
        $this->authorize('annuler', $mouvement);

        $action->executer($mouvement, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Mouvement annulé.']);
        }
        return back()->with('success', 'Mouvement annulé.');
    }
}