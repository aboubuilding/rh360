<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\DecisionFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Models\DecisionInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Interim\Requests\CloturerInterimInterneRequest;
use App\Domain\Interim\Requests\DeciderFinInterimRequest;
use App\Domain\Interim\Requests\ProlongerInterimInterneRequest;
use App\Domain\Interim\Requests\StoreAvantageInterimRequest;
use App\Domain\Interim\Requests\StoreInterimInterneRequest;
use App\Domain\Interim\Services\InterimInterneService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Intérims internes d'un emploi supérieur (CCIT art. 13).
 */
class InterimController extends Controller
{
    public function __construct(private InterimInterneService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', InterimInterne::class);

        $interims = InterimInterne::query()
            ->with(['salarie', 'posteOccupe', 'categorieOccupee', 'titulaire'])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('salarie', fn ($s) => $s
                ->where('nom', 'like', "%{$request->q}%")
                ->orWhere('prenoms', 'like', "%{$request->q}%")
                ->orWhere('matricule', 'like', "%{$request->q}%")))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->orderByRaw("statut = 'a_regulariser' desc")
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        // Intérims saisis avant la refonte et non encore repris (commande interims:reprise).
        $nonRepris = MouvementCarriere::query()
            ->where('type_mouvement', TypeMouvement::INTERIM->value)
            ->whereNotIn('id', InterimInterne::query()->whereNotNull('mouvement_carriere_id')->select('mouvement_carriere_id'))
            ->with(['salarie', 'posteCible'])
            ->orderByDesc('date_effet')
            ->get();

        return view('carriere.interims.index', [
            'interims' => $interims,
            'nonRepris' => $nonRepris,
            'statuts' => StatutInterim::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', InterimInterne::class);

        return view('carriere.interims.create', [
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'postes' => Poste::with('categorieClassification')->where('actif', true)->orderBy('intitule')->get(),
            'absences' => Absence::with(['salarie', 'typeConge'])->where('debut_le', '>=', now()->subYear())->orderByDesc('debut_le')->get(),
            'motifs' => MotifInterim::options(),
        ]);
    }

    public function store(StoreInterimInterneRequest $request)
    {
        $this->authorize('create', InterimInterne::class);

        $interim = $this->service->creer($request->safe()->except('piece'), $request->file('piece'));

        return $this->repondre($request, 'Intérim créé.', route('carriere.interims.show', $interim));
    }

    public function show(InterimInterne $interim)
    {
        $this->authorize('view', $interim);

        $interim->load([
            'salarie', 'posteOccupe.structure', 'categorieOccupee', 'categorieOrigine', 'titulaire',
            'absenceTitulaire.typeConge', 'positionOrigine', 'affectationOrigine.poste',
            'mouvementCarriere', 'decisions.decidePar', 'avantages.rubrique', 'decidePar',
        ]);

        return view('carriere.interims.show', [
            'interim' => $interim,
            'causes' => collect(CauseFinInterim::cases())
                ->reject(fn ($c) => $c === CauseFinInterim::DECISION)
                ->mapWithKeys(fn ($c) => [$c->value => $c->libelle()])->all(),
            'decisions' => DecisionFinInterim::options(),
            'positionsCible' => PositionClassification::where('categorie_id', $interim->categorie_occupee_id)
                ->orderBy('ordre')->get(),
            'rubriques' => RubriquePaie::where('actif', true)
                ->whereNotIn('code', ['SAL-BASE', config('interim.rubrique_indemnite')])
                ->orderBy('nom')->get(),
        ]);
    }

    public function prolonger(ProlongerInterimInterneRequest $request, InterimInterne $interim)
    {
        $this->authorize('gerer', $interim);

        $this->service->prolonger(
            $interim,
            $request->date_fin_prevue,
            $request->date_fin_motif,
            $request->file('piece'),
            $request->commentaire,
        );

        return $this->repondre($request, 'Intérim prolongé.');
    }

    public function cloturer(CloturerInterimInterneRequest $request, InterimInterne $interim)
    {
        $this->authorize('gerer', $interim);

        $this->service->cloturer(
            $interim,
            CauseFinInterim::from($request->cause_fin),
            $request->date_fin_reelle,
            $request->commentaire,
            $request->file('piece'),
        );

        return $this->repondre($request, 'Intérim clôturé.');
    }

    public function decider(DeciderFinInterimRequest $request, InterimInterne $interim)
    {
        $this->authorize('gerer', $interim);

        $this->service->deciderFin(
            $interim,
            DecisionFinInterim::from($request->decision),
            $request->date_effet,
            $request->file('piece'),
            $request->integer('position_cible_id') ?: null,
            $request->filled('nouveau_salaire_base') ? $request->integer('nouveau_salaire_base') : null,
            $request->commentaire,
        );

        return $this->repondre($request, 'Décision enregistrée.');
    }

    public function ajouterAvantage(StoreAvantageInterimRequest $request, InterimInterne $interim)
    {
        $this->authorize('gerer', $interim);

        $this->service->ajouterAvantage($interim, $request->safe()->except('justificatif'), $request->file('justificatif'));

        return $this->repondre($request, 'Avantage enregistré.');
    }

    public function piece(InterimInterne $interim, DecisionInterim $decision)
    {
        $this->authorize('view', $interim);

        abort_unless(
            $decision->objet_type === $interim->getMorphClass()
                && (int) $decision->objet_id === $interim->id
                && $decision->chemin_piece
                && Storage::disk('local')->exists($decision->chemin_piece),
            404,
        );

        return Storage::disk('local')->download($decision->chemin_piece);
    }

    private function repondre(Request $request, string $message, ?string $redirection = null)
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter(['message' => $message, 'redirect' => $redirection]));
        }

        return ($redirection ? redirect($redirection) : back())->with('success', $message);
    }
}
