<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Actions\CalculerPeriode;
use App\Domain\Paie\Actions\OuvrirPeriode;
use App\Domain\Paie\Actions\ReouvrirPeriode;
use App\Domain\Paie\Actions\SaisirElementVariable;
use App\Domain\Paie\Actions\ValiderPeriode;
use App\Domain\Paie\Enums\StatutPeriode;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Requests\OuvrirPeriodeRequest;
use App\Domain\Paie\Requests\ReouvrirPeriodeRequest;
use App\Domain\Paie\Requests\SaisirElementVariableRequest;
use App\Domain\Paie\Requests\ValiderPeriodeRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PeriodePaieController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PeriodePaie::class);

        $periodes = PeriodePaie::query()
            ->deStatut($request->statut)
            ->recentes()
            ->paginate(24)
            ->withQueryString();

        return view('paie.periodes.index', [
            'periodes' => $periodes,
            'statuts' => StatutPeriode::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', PeriodePaie::class);
        return view('paie.periodes.create');
    }

    public function store(OuvrirPeriodeRequest $request, OuvrirPeriode $action)
    {
        $this->authorize('create', PeriodePaie::class);

        try {
            $periode = $action->executer(
                auth()->user()->entreprise_id,
                (int) $request->annee,
                (int) $request->mois,
            );
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Période ouverte.',
                'redirect' => route('paie.periodes.show', $periode),
            ]);
        }

        return redirect()->route('paie.periodes.show', $periode)
            ->with('success', 'Période ouverte.');
    }

    public function show(PeriodePaie $periode)
    {
        $this->authorize('view', $periode);

        $periode->load(['bulletins.salarie', 'validePar']);

        $salaries = Salarie::where('entreprise_id', $periode->entreprise_id)
            ->where('actif', true)
            ->orderBy('nom')
            ->get();

        $rubriquesVariables = RubriquePaie::where('entreprise_id', $periode->entreprise_id)
            ->actives()
            ->where('recurrence', 'variable')
            ->orderBy('nom')
            ->get();

        return view('paie.periodes.show', compact('periode', 'salaries', 'rubriquesVariables'));
    }

    public function calculer(Request $request, PeriodePaie $periode, CalculerPeriode $action)
    {
        $this->authorize('calculer', $periode);

        try {
            $resultats = $action->executer($periode);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        $message = "{$resultats['bulletins_calcules']} bulletin(s) calculé(s).";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function valider(ValiderPeriodeRequest $request, PeriodePaie $periode, ValiderPeriode $action)
    {
        $this->authorize('valider', $periode);

        try {
            $action->executer($periode);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Période validée définitivement.']);
        }

        return back()->with('success', 'Période validée définitivement.');
    }

    public function reouvrir(ReouvrirPeriodeRequest $request, PeriodePaie $periode, ReouvrirPeriode $action)
    {
        $this->authorize('reouvrir', $periode);

        try {
            $action->executer($periode, $request->motif);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Période réouverte.']);
        }

        return back()->with('success', 'Période réouverte.');
    }

    public function saisir(SaisirElementVariableRequest $request, PeriodePaie $periode, SaisirElementVariable $action)
    {
        $this->authorize('saisir', $periode);

        try {
            $action->executer(
                $periode,
                (int) $request->salarie_id,
                (int) $request->rubrique_id,
                (float) ($request->quantite ?? 1),
                (float) ($request->taux ?? 0),
                (float) $request->montant,
                $request->observations,
            );
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Saisie enregistrée.']);
        }

        return back()->with('success', 'Saisie enregistrée.');
    }

    public function destroySaisie(PeriodePaie $periode, int $saisieId, SaisirElementVariable $action)
    {
        $this->authorize('saisir', $periode);

        $action->supprimer($periode, $saisieId);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Saisie supprimée.']);
        }

        return back()->with('success', 'Saisie supprimée.');
    }

    public function destroy(PeriodePaie $periode)
    {
        $this->authorize('supprimer', $periode);
        $periode->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Période supprimée.']);
        }

        return redirect()->route('paie.periodes.index')
            ->with('success', 'Période supprimée.');
    }

    public function journal(PeriodePaie $periode)
    {
        $this->authorize('exporter', \App\Domain\Paie\Models\BulletinPaie::class);

        $bulletinIds = $periode->bulletins()->pluck('id');
        $lignes = \App\Domain\Paie\Models\LigneBulletin::whereIn('bulletin_id', $bulletinIds)
            ->with('bulletin.salarie')
            ->orderBy('ordre')
            ->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($lignes) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings
            {
                public function __construct(private $lignes) {}

                public function array(): array
                {
                    return $this->lignes->map(fn ($l) => [
                        $l->bulletin?->salarie?->matricule,
                        $l->bulletin?->salarie?->nom_complet,
                        $l->code,
                        $l->libelle,
                        $l->nature,
                        (float) $l->montant,
                    ])->toArray();
                }

                public function headings(): array
                {
                    return ['Matricule', 'Salarié', 'Code', 'Libellé', 'Nature', 'Montant'];
                }
            },
            "journal-paie-{$periode->code}.xlsx"
        );
    }
}