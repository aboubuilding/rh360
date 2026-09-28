<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Models\DemandeConge;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanningCongeController extends Controller
{
    /**
     * Vue planning annuel : toutes les demandes d'une année, groupées par salarié et par mois.
     */
    public function index(Request $request)
    {
        $this->authorize('permission', 'conges.view');

        $annee = (int) $request->get('annee', now()->year);
        $structureId = $request->structure_id ? (int) $request->structure_id : null;

        $demandes = DemandeConge::query()
            ->with(['salarie', 'typeConge'])
            ->whereYear('date_debut', $annee)
            ->when($structureId, function ($q) use ($structureId) {
                $q->whereHas('salarie.affectations', function ($q) use ($structureId) {
                    $q->where('structure_id', $structureId)->where('en_cours', true);
                });
            })
            ->orderBy('date_debut')
            ->get();

        $structures = \App\Domain\Organisation\Models\Structure::orderBy('nom')->get();

        return view('conges.planning.index', [
            'demandes' => $demandes,
            'annee' => $annee,
            'structures' => $structures,
        ]);
    }

    public function modele()
    {
        $this->authorize('importerPlanning', DemandeConge::class);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings
            {
                public function array(): array { return []; }
                public function headings(): array
                {
                    return ['matricule', 'type_code', 'date_debut', 'date_reprise', 'motif'];
                }
            },
            'modele-planning-conges.xlsx'
        );
    }

    public function apercuImport(Request $request)
    {
        $this->authorize('importerPlanning', DemandeConge::class);

        $request->validate([
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $cheminTemp = $request->file('fichier')->store('conges/planning_temp', 'local');
        $lignes = \Maatwebsite\Excel\Facades\Excel::toCollection(
            null, \Illuminate\Support\Facades\Storage::disk('local')->path($cheminTemp)
        )->first();

        $entetes = $lignes->first() ?? [];
        $corps = $lignes->slice(1)->take(50);

        return view('conges.planning.apercu', [
            'entetes' => $entetes,
            'corps' => $corps,
            'fichierTemp' => $cheminTemp,
        ]);
    }

    public function importer(Request $request)
    {
        $this->authorize('importerPlanning', DemandeConge::class);

        $donnees = $request->validate([
            'fichier_temp' => ['required', 'string'],
        ]);

        $chemin = \Illuminate\Support\Facades\Storage::disk('local')->path($donnees['fichier_temp']);
        if (! file_exists($chemin)) {
            return redirect()->route('conges.planning.index')
                ->with('error', 'Fichier temporaire introuvable.');
        }

        $lignes = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $chemin)->first();
        $entetes = $lignes->first()->toArray();
        $corps = $lignes->slice(1);

        $importes = 0;
        $erreurs = [];

        DB::beginTransaction();
        try {
            foreach ($corps as $index => $ligne) {
                $row = array_combine($entetes, $ligne->toArray());

                $matricule = $row['matricule'] ?? null;
                $typeCode = $row['type_code'] ?? null;
                $dateDebut = $row['date_debut'] ?? null;
                $dateReprise = $row['date_reprise'] ?? null;

                if (! $matricule || ! $typeCode || ! $dateDebut) {
                    $erreurs[] = "Ligne " . ($index + 2) . " : données incomplètes.";
                    continue;
                }

                $salarie = \App\Domain\Personnel\Models\Salarie::where('matricule', $matricule)->first();
                $type = \App\Domain\Conges\Models\TypeConge::where('code', $typeCode)->first();

                if (! $salarie || ! $type) {
                    $erreurs[] = "Ligne " . ($index + 2) . " : salarié ou type introuvable.";
                    continue;
                }

                try {
                    app(\App\Domain\Conges\Actions\CreerDemandeConge::class)->executer([
                        'salarie_id' => $salarie->id,
                        'type_conge_id' => $type->id,
                        'date_debut' => $dateDebut,
                        'date_reprise' => $dateReprise,
                        'motif' => $row['motif'] ?? 'Import planning annuel',
                    ]);
                    $importes++;
                } catch (\Throwable $e) {
                    $erreurs[] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur d\'import : ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Storage::disk('local')->delete($donnees['fichier_temp']);

        return redirect()->route('conges.planning.index')
            ->with('success', "{$importes} demande(s) importée(s).")
            ->with('erreurs_import', $erreurs);
    }
}