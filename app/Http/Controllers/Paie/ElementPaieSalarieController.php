<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Actions\AffecterElementFixe;
use App\Domain\Paie\Models\AffectationModelePaie;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\ModelePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Services\ResolveurModelePaie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Paie d'un salarié : modèle de bulletin appliqué, éléments individuels (montant, période de
 * validité, exclusion d'une rubrique du modèle) et association manuelle à un autre modèle.
 */
class ElementPaieSalarieController extends Controller
{
    public function __construct(private ResolveurModelePaie $resolveur) {}

    public function index(Request $request)
    {
        $this->authorize('permission', 'paie.view');

        $salaries = Salarie::where('actif', true)
            ->when($request->q, fn ($q, $terme) => $q->where(fn ($q) => $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenoms', 'like', "%{$terme}%")->orWhere('matricule', 'like', "%{$terme}%")))
            ->orderBy('nom')
            ->paginate(50)
            ->withQueryString();

        $salaireBase = ElementPaieSalarie::whereIn('salarie_id', $salaries->pluck('id'))
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
            ->pluck('montant', 'salarie_id');

        return view('paie.elements.index', [
            'salaries' => $salaries,
            'salaireBase' => $salaireBase,
            'modeles' => $salaries->mapWithKeys(fn ($s) => [$s->id => $this->resolveur->pour($s, today())]),
        ]);
    }

    public function show(Salarie $salarie)
    {
        $this->authorize('permission', 'paie.view');

        $resolution = $this->resolveur->pour($salarie, today());
        $elements = ElementPaieSalarie::where('salarie_id', $salarie->id)->with('rubrique')->get()->keyBy('rubrique_id');

        return view('paie.elements.show', [
            'salarie' => $salarie,
            'resolution' => $resolution,
            'lignesModele' => $resolution['modele']?->rubriquesActives()->with('rubrique')->get() ?? collect(),
            'elements' => $elements,
            'rubriques' => RubriquePaie::actives()->whereNotIn('code', ['PRIME-ANC', 'HS', 'RAPPEL', 'CNSS', 'AMU', 'IRPP'])->orderBy('nom')->get(),
            'modeles' => ModelePaie::where('actif', true)->orderBy('nom')->get(),
            'affectations' => AffectationModelePaie::where('salarie_id', $salarie->id)->with('modele')->orderByDesc('debut')->get(),
            'prets' => \App\Domain\Paie\Models\PretSalarie::where('salarie_id', $salarie->id)->orderByDesc('id')->get(),
            'saisies' => \App\Domain\Paie\Models\SaisieAttribution::where('salarie_id', $salarie->id)->orderBy('rang')->get(),
        ]);
    }

    public function enregistrer(Request $request, Salarie $salarie, AffecterElementFixe $action)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'rubrique_id' => ['required', Rule::exists('rubriques_paie', 'id')->where('entreprise_id', $salarie->entreprise_id)],
            'montant' => ['nullable', 'numeric', 'min:0', 'required_unless:exclu,1'],
            'debut' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut'],
            'exclu' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:500'],
            'accord_le' => ['nullable', 'date'],
        ], [
            'rubrique_id.required' => 'La rubrique est obligatoire.',
            'montant.required_unless' => 'Le montant est obligatoire, sauf pour exclure la rubrique.',
            'fin.after_or_equal' => 'La date de fin doit suivre la date de début.',
        ]);

        try {
            $action->executer($salarie->id, (int) $donnees['rubrique_id'], (float) ($donnees['montant'] ?? 0), $donnees['observations'] ?? null,
                $donnees['debut'] ?? null, $donnees['fin'] ?? null, $request->boolean('exclu'), $donnees['accord_le'] ?? null);
        } catch (\DomainException $e) {
            return $this->repondre($request, $salarie, $e->getMessage(), 'error');
        }

        return $this->repondre($request, $salarie, 'Élément de paie enregistré.');
    }

    public function supprimer(Request $request, Salarie $salarie, ElementPaieSalarie $element, AffecterElementFixe $action)
    {
        $this->authorize('permission', 'paie.manage');
        abort_unless($element->salarie_id === $salarie->id, 404);
        if ($element->rubrique?->code === 'SAL-BASE') {
            return $this->repondre($request, $salarie, 'Le salaire de base ne se supprime pas : modifiez son montant.', 'error');
        }

        $action->supprimer($element->id);

        return $this->repondre($request, $salarie, 'Élément de paie retiré.');
    }

    public function associer(Request $request, Salarie $salarie)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'modele_id' => ['required', Rule::exists('modeles_paie', 'id')->where('entreprise_id', $salarie->entreprise_id)],
            'debut' => ['required', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut'],
            'motif' => ['required', 'string', 'max:500'],
        ], [
            'modele_id.required' => 'Le modèle est obligatoire.',
            'debut.required' => 'La date de début est obligatoire.',
            'motif.required' => 'Le motif est obligatoire.',
        ]);

        // Deux associations manuelles ne se chevauchent pas.
        $chevauche = AffectationModelePaie::where('salarie_id', $salarie->id)
            ->where(fn ($q) => $q->whereNull('fin')->orWhere('fin', '>=', $donnees['debut']))
            ->when($donnees['fin'] ?? null, fn ($q, $fin) => $q->where('debut', '<=', $fin))
            ->exists();
        if ($chevauche) {
            return $this->repondre($request, $salarie, 'Une autre association de modèle couvre déjà ces dates.', 'error');
        }

        AffectationModelePaie::create($donnees + ['entreprise_id' => $salarie->entreprise_id, 'salarie_id' => $salarie->id, 'cree_par' => auth()->id()]);

        return $this->repondre($request, $salarie, 'Modèle associé au salarié.');
    }

    public function dissocier(Request $request, Salarie $salarie, AffectationModelePaie $affectation)
    {
        $this->authorize('permission', 'paie.manage');
        abort_unless($affectation->salarie_id === $salarie->id, 404);
        $affectation->delete();

        return $this->repondre($request, $salarie, 'Association retirée : le modèle de la catégorie s\'applique.');
    }

    // Prêt ou avance remboursé par cession volontaire, vérifiée par l'inspecteur du travail ou le tribunal.
    public function enregistrerPret(Request $request, Salarie $salarie)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'type' => ['required', Rule::in(array_keys(\App\Domain\Paie\Models\PretSalarie::TYPES))],
            'montant' => ['required', 'numeric', 'min:1'],
            'echeance' => ['required', 'numeric', 'min:1', 'lte:montant'],
            'premier_mois' => ['required', 'date'],
            'verification' => ['required', Rule::in(array_keys(\App\Domain\Paie\Models\PretSalarie::VERIFICATIONS))],
            'verifie_le' => ['nullable', 'date'],
            'motif' => ['nullable', 'string', 'max:500'],
        ], [
            'echeance.lte' => 'L\'échéance ne peut pas dépasser le montant prêté.',
            'premier_mois.required' => 'Le premier mois de remboursement est obligatoire.',
        ]);

        \App\Domain\Paie\Models\PretSalarie::create($donnees + [
            'entreprise_id' => $salarie->entreprise_id, 'salarie_id' => $salarie->id, 'statut' => 'actif', 'cree_par' => auth()->id(),
        ]);

        return $this->repondre($request, $salarie, empty($donnees['verifie_le'])
            ? 'Prêt enregistré. Il ne sera retenu qu\'une fois la vérification de la cession enregistrée.'
            : 'Prêt enregistré.');
    }

    public function verifierPret(Request $request, Salarie $salarie, \App\Domain\Paie\Models\PretSalarie $pret)
    {
        $this->authorize('permission', 'paie.manage');
        abort_unless($pret->salarie_id === $salarie->id, 404);
        $pret->update($request->validate(['verifie_le' => ['required', 'date']]));

        return $this->repondre($request, $salarie, 'Vérification de la cession enregistrée.');
    }

    public function enregistrerSaisie(Request $request, Salarie $salarie)
    {
        $this->authorize('permission', 'paie.manage');

        $donnees = $request->validate([
            'creancier' => ['required', 'string', 'max:200'],
            'reference_acte' => ['required', 'string', 'max:120'],
            'date_acte' => ['required', 'date'],
            'montant_total' => ['required', 'numeric', 'min:1'],
            'rang' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'creancier.required' => 'Le créancier est obligatoire.',
            'reference_acte.required' => 'La référence de l\'acte est obligatoire.',
        ]);

        \App\Domain\Paie\Models\SaisieAttribution::create($donnees + [
            'entreprise_id' => $salarie->entreprise_id, 'salarie_id' => $salarie->id, 'rang' => $donnees['rang'] ?? 1,
            'statut' => 'active', 'cree_par' => auth()->id(),
        ]);

        return $this->repondre($request, $salarie, 'Saisie-attribution enregistrée.');
    }

    private function repondre(Request $request, Salarie $salarie, string $message, string $type = 'success')
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $type === 'success' ? 200 : 422);
        }

        return redirect()->route('paie.elements.show', $salarie)->with($type, $message);
    }
}
