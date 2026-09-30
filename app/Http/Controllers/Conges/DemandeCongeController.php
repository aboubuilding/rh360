<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Actions\AnnulerDemandeConge;
use App\Domain\Conges\Actions\AutoriserDemandeConge;
use App\Domain\Conges\Actions\ConfirmerReprise;
use App\Domain\Conges\Actions\CreerDemandeConge;
use App\Domain\Conges\Actions\DemarrerConge;
use App\Domain\Conges\Actions\ModifierDemandeConge;
use App\Domain\Conges\Actions\ProgrammerDemandeConge;
use App\Domain\Conges\Actions\RefuserDemandeConge;
use App\Domain\Conges\Actions\SoumettreDemandeConge;
use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Requests\AnnulerDemandeCongeRequest;
use App\Domain\Conges\Requests\AutoriserDemandeCongeRequest;
use App\Domain\Conges\Requests\RefuserDemandeCongeRequest;
use App\Domain\Conges\Requests\StoreDemandeCongeRequest;
use App\Domain\Conges\Requests\UpdateDemandeCongeRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DemandeCongeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DemandeConge::class);

        $vue = $request->get('vue', 'toutes');

        $query = DemandeConge::query()->with(['salarie', 'typeConge', 'validePar']);

        match ($vue) {
            'a_traiter' => $query->where('statut', StatutDemandeConge::SOUMISE->value),
            'programmes' => $query->programmees(),
            'en_cours' => $query->enCours(),
            'reprises_retard' => $query->reprisesEnRetard(),
            default => null,
        };

        $demandes = $query
            ->recherche($request->q)
            ->deStatut($request->statut)
            ->deType($request->type_conge_id ? (int) $request->type_conge_id : null)
            ->periode($request->du, $request->au)
            ->orderByDesc('date_debut')
            ->paginate(50)
            ->withQueryString();

        return view('conges.demandes.index', [
            'demandes' => $demandes,
            'vue' => $vue,
            'statuts' => StatutDemandeConge::options(),
            'types' => TypeConge::orderBy('nom')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', DemandeConge::class);

        $salarie = $request->filled('salarie_id')
            ? Salarie::findOrFail($request->salarie_id)
            : null;

        return view('conges.demandes.create', [
            'salarie' => $salarie,
            'salaries' => Salarie::orderBy('nom')->get(),
            'types' => TypeConge::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreDemandeCongeRequest $request, CreerDemandeConge $action)
    {
        $this->authorize('create', DemandeConge::class);

        $demande = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Demande créée.',
                'redirect' => route('conges.demandes.show', $demande),
            ]);
        }

        return redirect()->route('conges.demandes.show', $demande)
            ->with('success', 'Demande créée.');
    }

    public function show(DemandeConge $demande)
    {
        $this->authorize('view', $demande);

        $demande->load(['salarie', 'typeConge', 'creePar', 'validePar']);

        // Charger le solde correspondant
        $solde = null;
        if ($demande->typeConge && (float) $demande->typeConge->droit_annuel > 0) {
            $annee = $demande->date_debut?->year ?? now()->year;
            $solde = \App\Domain\Conges\Models\SoldeConge::where('salarie_id', $demande->salarie_id)
                ->where('type_conge_id', $demande->type_conge_id)
                ->where('annee', $annee)
                ->first();
        }

        return view('conges.demandes.show', compact('demande', 'solde'));
    }

    public function edit(DemandeConge $demande)
    {
        $this->authorize('update', $demande);

        return view('conges.demandes.edit', [
            'demande' => $demande,
            // Le salarié d'une demande existante n'est pas modifiable : affiché en lecture seule
            'salarie' => $demande->salarie,
            'types' => TypeConge::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function update(UpdateDemandeCongeRequest $request, DemandeConge $demande, ModifierDemandeConge $action)
    {
        $this->authorize('update', $demande);

        $action->executer($demande, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande mise à jour.']);
        }

        return redirect()->route('conges.demandes.show', $demande)
            ->with('success', 'Demande mise à jour.');
    }

    public function destroy(DemandeConge $demande)
    {
        $this->authorize('delete', $demande);
        $demande->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Demande supprimée.']);
        }

        return redirect()->route('conges.demandes.index')
            ->with('success', 'Demande supprimée.');
    }

    // --- Transitions ---

    public function soumettre(Request $request, DemandeConge $demande, SoumettreDemandeConge $action)
    {
        $this->authorize('soumettre', $demande);

        try {
            $action->executer($demande);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande soumise.']);
        }
        return back()->with('success', 'Demande soumise.');
    }

    public function autoriser(AutoriserDemandeCongeRequest $request, DemandeConge $demande, AutoriserDemandeConge $action)
    {
        $this->authorize('autoriser', $demande);

        try {
            $action->executer($demande, $request->reference_acte, $request->date_acte);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande autorisée.']);
        }
        return back()->with('success', 'Demande autorisée.');
    }

    public function refuser(RefuserDemandeCongeRequest $request, DemandeConge $demande, RefuserDemandeConge $action)
    {
        $this->authorize('refuser', $demande);

        $action->executer($demande, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande refusée.']);
        }
        return back()->with('success', 'Demande refusée.');
    }

    public function programmer(Request $request, DemandeConge $demande, ProgrammerDemandeConge $action)
    {
        $this->authorize('programmer', $demande);

        $action->executer($demande);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande programmée.']);
        }
        return back()->with('success', 'Demande programmée.');
    }

    public function demarrer(Request $request, DemandeConge $demande, DemarrerConge $action)
    {
        $this->authorize('demarrer', $demande);

        $action->executer($demande);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Congé démarré.']);
        }
        return back()->with('success', 'Congé démarré.');
    }

    public function confirmerReprise(Request $request, DemandeConge $demande, ConfirmerReprise $action)
    {
        $this->authorize('confirmerReprise', $demande);

        $donnees = $request->validate([
            'date_reprise_reelle' => ['nullable', 'date'],
        ]);

        $action->executer($demande, $donnees['date_reprise_reelle'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reprise confirmée.']);
        }
        return back()->with('success', 'Reprise confirmée.');
    }

    public function annuler(AnnulerDemandeCongeRequest $request, DemandeConge $demande, AnnulerDemandeConge $action)
    {
        $this->authorize('annuler', $demande);

        $action->executer($demande, $request->motif);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Demande annulée.']);
        }
        return back()->with('success', 'Demande annulée.');
    }

    public function imprimerActe(DemandeConge $demande)
    {
        $this->authorize('view', $demande);

        // Génération PDF via barryvdh/laravel-dompdf
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('conges.demandes.acte-pdf', [
            'demande' => $demande->load(['salarie', 'typeConge']),
        ]);

        return $pdf->download("acte-conge-{$demande->numero_demande}.pdf");
    }
}