<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Personnel\Imports\SalariesImport;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportSalarieController extends Controller
{
    public function formulaire()
    {
        $this->authorize('importer', Salarie::class);
        return view('personnel.salaries.import');
    }

    /**
     * Aperçu : stocke le fichier temp, renvoie les 20 premières lignes.
     */
    public function apercu(Request $request)
    {
        $this->authorize('importer', Salarie::class);

        $request->validate([
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.mimes' => 'Le fichier doit être au format XLSX, XLS ou CSV.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        // Stocker le fichier en temp pour la 2e étape
        $cheminTemp = $request->file('fichier')->store('imports/salaries_temp', 'local');

        // Lire les premières lignes pour l'aperçu
        $lignes = Excel::toCollection(null, Storage::disk('local')->path($cheminTemp))->first();
        $entetes = $lignes->first() ?? [];
        $corps = $lignes->slice(1)->take(20);

        return view('personnel.salaries.import-apercu', [
            'entetes' => $entetes,
            'corps' => $corps,
            'fichierTemp' => $cheminTemp,
        ]);
    }

    /**
     * Import définitif : reprend le fichier temp.
     */
    public function importer(Request $request)
    {
        $this->authorize('importer', Salarie::class);

        $donnees = $request->validate([
            'fichier_temp' => ['required', 'string'],
        ]);

        $cheminComplet = Storage::disk('local')->path($donnees['fichier_temp']);
        if (! file_exists($cheminComplet)) {
            return redirect()->route('personnel.salaries.import')
                ->with('error', 'Fichier temporaire introuvable. Veuillez recommencer.');
        }

        try {
            $import = new SalariesImport(app(\App\Domain\Personnel\Actions\CreerSalarie::class));
            Excel::import($import, $cheminComplet);
        } finally {
            // Nettoyage du fichier temp, même en cas d'erreur
            Storage::disk('local')->delete($donnees['fichier_temp']);
        }

        $message = "{$import->importes} salarié(s) importé(s).";
        if (count($import->erreurs) > 0) {
            $message .= " " . count($import->erreurs) . " erreur(s).";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('personnel.salaries.index'),
            ]);
        }

        return redirect()->route('personnel.salaries.index')
            ->with('success', $message)
            ->with('erreurs_import', $import->erreurs);
    }

    /**
     * Génère et télécharge le modèle Excel.
     */
    public function modele()
    {
        $this->authorize('importer', Salarie::class);

        $entetes = [
            'nom', 'prenoms', 'sexe', 'date_naissance', 'lieu_naissance',
            'nationalite', 'telephone_principal', 'email_personnel',
            'adresse', 'ville', 'numero_cnss', 'date_embauche',
            'type_contrat', 'reference_contrat',
        ];

        return Excel::download(
            new class($entetes) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings
            {
                public function __construct(private array $entetes) {}
                public function array(): array { return []; }
                public function headings(): array { return $this->entetes; }
            },
            'modele_import_salaries.xlsx'
        );
    }
}