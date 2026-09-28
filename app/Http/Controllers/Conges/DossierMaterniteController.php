<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Actions\EnregistrerDossierMaternite;
use App\Domain\Conges\Enums\StatutDossierMaternite;
use App\Domain\Conges\Models\DossierMaternite;
use App\Domain\Conges\Requests\StoreDossierMaterniteRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DossierMaterniteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DossierMaternite::class);

        $dossiers = DossierMaternite::query()
            ->with('salarie')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('salarie', function ($q) use ($request) {
                $q->where('nom', 'like', "%{$request->q}%")
                  ->orWhere('prenoms', 'like', "%{$request->q}%");
            }))
            ->orderByDesc('date_declaration')
            ->paginate(50)
            ->withQueryString();

        return view('conges.maternite.index', [
            'dossiers' => $dossiers,
            'statuts' => StatutDossierMaternite::options(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', DossierMaternite::class);

        return view('conges.maternite.create', [
            'salarie' => null,
            'salariees' => Salarie::where('sexe', 'F')->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreDossierMaterniteRequest $request, EnregistrerDossierMaternite $action)
    {
        $this->authorize('create', DossierMaternite::class);

        $donnees = $request->validated();
        $certificat = $request->file('certificat_medical');
        unset($donnees['certificat_medical']);

        $dossier = $action->executer($donnees, $certificat);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Dossier de maternité enregistré.',
                'redirect' => route('conges.maternite.show', $dossier),
            ]);
        }

        return redirect()->route('conges.maternite.show', $dossier)
            ->with('success', 'Dossier de maternité enregistré.');
    }

    public function show(DossierMaternite $maternite)
    {
        $this->authorize('view', $maternite);
        $maternite->load(['salarie', 'creePar']);
        return view('conges.maternite.show', ['dossier' => $maternite]);
    }

    public function edit(DossierMaternite $maternite)
    {
        $this->authorize('update', $maternite);

        return view('conges.maternite.edit', [
            'dossier' => $maternite,
            'salariees' => Salarie::where('sexe', 'F')->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function update(StoreDossierMaterniteRequest $request, DossierMaternite $maternite, EnregistrerDossierMaternite $action)
    {
        $this->authorize('update', $maternite);

        $donnees = $request->validated();
        $certificat = $request->file('certificat_medical');
        unset($donnees['certificat_medical']);

        if ($certificat) {
            $donnees['chemin_certificat_medical'] = $certificat->store('conges/maternite', 'local');
        }

        $maternite->update($donnees);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Dossier mis à jour.']);
        }

        return redirect()->route('conges.maternite.show', $maternite)
            ->with('success', 'Dossier mis à jour.');
    }

    public function certificat(DossierMaternite $maternite)
    {
        $this->authorize('view', $maternite);

        if (! $maternite->chemin_certificat_medical
            || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($maternite->chemin_certificat_medical)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')
            ->response($maternite->chemin_certificat_medical);
    }

    public function exporter()
    {
        $this->authorize('exporter', DossierMaternite::class);

        $dossiers = DossierMaternite::with('salarie')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($dossiers) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings
            {
                public function __construct(private $dossiers) {}
                public function array(): array
                {
                    return $this->dossiers->map(fn ($d) => [
                        $d->salarie?->matricule,
                        $d->salarie?->nom_complet,
                        $d->date_declaration?->format('d/m/Y'),
                        $d->date_prevue_accouchement?->format('d/m/Y'),
                        $d->statut?->libelle(),
                    ])->toArray();
                }
                public function headings(): array
                {
                    return ['Matricule', 'Salariée', 'Date déclaration', 'Date accouchement', 'Statut'];
                }
            },
            'registre-maternite-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}