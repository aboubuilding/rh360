<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Interim\Enums\StatutMission;
use App\Domain\Interim\Enums\TypeMotifMission;
use App\Domain\Interim\Models\EntrepriseInterim;
use App\Domain\Interim\Models\MissionInterim;
use App\Domain\Interim\Requests\ActionMissionInterimRequest;
use App\Domain\Interim\Requests\StoreMissionInterimRequest;
use App\Domain\Interim\Services\MissionInterimService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Missions de travail intérimaire (Code du travail, art. 60 à 63) : personnel externe,
 * hors effectif et hors paie.
 */
class MissionInterimController extends Controller
{
    public function __construct(private MissionInterimService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', MissionInterim::class);

        $missions = MissionInterim::with(['agence', 'poste'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        return view('carriere.interims.missions.index', [
            'missions' => $missions,
            'statuts' => StatutMission::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', MissionInterim::class);

        return view('carriere.interims.missions.create', [
            'agences' => EntrepriseInterim::orderBy('raison_sociale')->get(),
            'postes' => Poste::where('actif', true)->orderBy('intitule')->get(),
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
            'typesMotif' => TypeMotifMission::options(),
        ]);
    }

    public function store(StoreMissionInterimRequest $request)
    {
        $this->authorize('create', MissionInterim::class);

        $mission = $this->service->creer($request->validated());

        return $this->repondre($request, 'Mission enregistrée.', route('carriere.interims.missions.show', $mission), $mission->alerte_salaire);
    }

    public function show(MissionInterim $mission)
    {
        $this->authorize('view', $mission);

        $mission->load(['agence', 'poste.categorieClassification', 'titulaireRemplace', 'sstValidePar',
            'decisions.decidePar', 'factures', 'alertes' => fn ($q) => $q->where('en_cours', true)]);

        return view('carriere.interims.missions.show', compact('mission'));
    }

    public function checklist(ActionMissionInterimRequest $request, MissionInterim $mission)
    {
        $this->authorize('gerer', $mission);
        $this->service->validerChecklist($mission, $request->safe()->except('piece'), $request->file('piece'));

        return $this->repondre($request, 'Checklist SST validée.');
    }

    public function demarrer(Request $request, MissionInterim $mission)
    {
        $this->authorize('gerer', $mission);
        $this->service->demarrer($mission);

        return $this->repondre($request, 'Mission démarrée.');
    }

    public function renouveler(ActionMissionInterimRequest $request, MissionInterim $mission)
    {
        $this->authorize('gerer', $mission);
        $mission = $this->service->renouveler($mission, $request->date_fin, $request->commentaire, $request->file('piece'));

        return $this->repondre($request, 'Mission renouvelée.', null, $mission->alerte_salaire);
    }

    public function terminer(ActionMissionInterimRequest $request, MissionInterim $mission)
    {
        $this->authorize('gerer', $mission);
        $this->service->terminer($mission, $request->date_fin_reelle, $request->commentaire);

        return $this->repondre($request, 'Mission terminée.');
    }

    public function annuler(ActionMissionInterimRequest $request, MissionInterim $mission)
    {
        $this->authorize('gerer', $mission);
        $this->service->annuler($mission, $request->motif_annulation);

        return $this->repondre($request, 'Mission annulée.');
    }

    private function repondre(Request $request, string $message, ?string $redirection = null, ?string $alerte = null)
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter(['message' => $message, 'redirect' => $redirection, 'alerte' => $alerte]));
        }

        $reponse = ($redirection ? redirect($redirection) : back())->with('success', $message);

        return $alerte ? $reponse->with('warning', $alerte) : $reponse;
    }
}
