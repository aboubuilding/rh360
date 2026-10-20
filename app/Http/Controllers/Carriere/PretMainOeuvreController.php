<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Interim\Enums\StatutPret;
use App\Domain\Interim\Enums\TypePiecePret;
use App\Domain\Interim\Models\PretMainOeuvre;
use App\Domain\Interim\Requests\ActionPretMainOeuvreRequest;
use App\Domain\Interim\Requests\StorePretMainOeuvreRequest;
use App\Domain\Interim\Services\PretMainOeuvreService;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Prêts de main-d'œuvre à but non lucratif (Code du travail, art. 8).
 */
class PretMainOeuvreController extends Controller
{
    public function __construct(private PretMainOeuvreService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PretMainOeuvre::class);

        return view('carriere.interims.prets.index', [
            'prets' => PretMainOeuvre::with('salarie')
                ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
                ->orderByDesc('date_debut')->paginate(50)->withQueryString(),
            'statuts' => StatutPret::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', PretMainOeuvre::class);

        return view('carriere.interims.prets.create', [
            'salaries' => Salarie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StorePretMainOeuvreRequest $request)
    {
        $this->authorize('create', PretMainOeuvre::class);

        $pret = $this->service->creer($request->validated());

        return $this->repondre($request, 'Prêt enregistré.', route('carriere.interims.prets.show', $pret));
    }

    public function show(PretMainOeuvre $pret)
    {
        $this->authorize('view', $pret);

        $pret->load(['salarie', 'posteOrigine.categorieClassification', 'posteRetour', 'positionOrigine', 'pieces',
            'refacturations', 'decisions.decidePar', 'mouvementCarriere', 'alertes' => fn ($q) => $q->where('en_cours', true)]);

        return view('carriere.interims.prets.show', [
            'pret' => $pret,
            'typesPieces' => TypePiecePret::options(),
            'postes' => Poste::where('actif', true)->orderBy('intitule')->get(),
        ]);
    }

    public function piece(ActionPretMainOeuvreRequest $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $this->service->ajouterPiece($pret, TypePiecePret::from($request->type_piece), $request->date_signature, $request->file('fichier'));

        return $this->repondre($request, 'Pièce enregistrée.');
    }

    public function refus(ActionPretMainOeuvreRequest $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $this->service->enregistrerRefus($pret, $request->date_reponse_salarie, $request->commentaire, $request->file('fichier'));

        return $this->repondre($request, 'Refus du salarié enregistré, sans aucune conséquence pour lui.');
    }

    public function demarrer(Request $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $this->service->demarrer($pret);

        return $this->repondre($request, 'Prêt démarré.');
    }

    public function refacturation(ActionPretMainOeuvreRequest $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $refacturation = $this->service->enregistrerRefacturation($pret, $request->validated());

        return $this->repondre($request, 'Refacturation enregistrée.', null, $refacturation->alerte_marge);
    }

    public function retour(ActionPretMainOeuvreRequest $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $this->service->retour($pret, $request->date_retour, $request->integer('poste_retour_id') ?: null, $request->commentaire);

        return $this->repondre($request, 'Retour du salarié enregistré.');
    }

    public function annuler(ActionPretMainOeuvreRequest $request, PretMainOeuvre $pret)
    {
        $this->authorize('gerer', $pret);
        $this->service->annuler($pret, $request->motif_annulation);

        return $this->repondre($request, 'Prêt annulé.');
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
