<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Actions\CloturerInterim;
use App\Domain\Carriere\Actions\CreerMouvement;
use App\Domain\Carriere\Actions\ProlongerInterim;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Requests\CloturerInterimRequest;
use App\Domain\Carriere\Requests\ProlongerInterimRequest;
use App\Domain\Carriere\Requests\StoreMouvementRequest;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InterimController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MouvementCarriere::class);

        $interims = MouvementCarriere::query()
            ->where('type_mouvement', TypeMouvement::INTERIM->value)
            ->with(['salarie', 'posteDepart', 'posteCible', 'structureCible'])
            ->when($request->filled('q'), fn ($q) => $q->recherche($request->q))
            ->when($request->filled('statut'), function ($q) use ($request) {
                if ($request->statut === 'en_cours') {
                    $q->whereNotIn('statut', ['completed', 'cancelled', 'rejected']);
                } elseif ($request->statut === 'clotures') {
                    $q->where('statut', 'completed');
                }
            })
            ->orderByDesc('date_effet')
            ->paginate(50)
            ->withQueryString();

        return view('carriere.interims.index', compact('interims'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', MouvementCarriere::class);

        return view('carriere.interims.create', [
            'salaries' => Salarie::orderBy('nom')->get(),
            'structures' => Structure::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
        ]);
    }

    public function store(StoreMouvementRequest $request, CreerMouvement $action)
    {
        $this->authorize('create', MouvementCarriere::class);

        $donnees = $request->validated();
        $donnees['type_mouvement'] = TypeMouvement::INTERIM->value;

        $interim = $action->executer($donnees);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Intérim créé.',
                'redirect' => route('carriere.interims.show', $interim),
            ]);
        }

        return redirect()->route('carriere.interims.show', $interim)
            ->with('success', 'Intérim créé.');
    }

    public function show(MouvementCarriere $interim)
    {
        $this->authorize('view', $interim);

        if ($interim->type_mouvement->value !== 'interim') {
            abort(404);
        }

        $interim->load(['salarie', 'posteDepart', 'posteCible', 'structureCible', 'creePar']);

        return view('carriere.interims.show', compact('interim'));
    }

    public function prolonger(ProlongerInterimRequest $request, MouvementCarriere $interim, ProlongerInterim $action)
    {
        $this->authorize('gererInterim', $interim);

        $action->executer($interim, $request->date_fin_prevue, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Intérim prolongé.']);
        }
        return back()->with('success', 'Intérim prolongé.');
    }

    public function cloturer(CloturerInterimRequest $request, MouvementCarriere $interim, CloturerInterim $action)
    {
        $this->authorize('gererInterim', $interim);

        $action->executer($interim, $request->date_fin_reelle, $request->motif_cloture);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Intérim clôturé.']);
        }
        return back()->with('success', 'Intérim clôturé.');
    }
}