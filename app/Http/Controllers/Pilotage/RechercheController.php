<?php

namespace App\Http\Controllers\Pilotage;

use App\Domain\Pilotage\Services\RechercheRapideSalarie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    public function __construct(private RechercheRapideSalarie $recherche) {}

    /**
     * Endpoint AJAX de recherche rapide de salariés (utilisé par la modale Ctrl+K).
     */
    public function salaries(Request $request)
    {
        abort_unless($request->user()->peut('salaries.view'), 403);

        $terme = (string) $request->get('q', '');

        $resultats = $this->recherche->rechercher(
            $request->user()->entreprise_id,
            $terme,
            10,
        );

        return response()->json($resultats);
    }

    /**
     * Aperçu rapide d'un salarié (widget du tableau de bord).
     */
    public function apercu(Request $request, Salarie $salarie)
    {
        abort_unless($request->user()->peut('salaries.view'), 403);
        abort_unless($salarie->entreprise_id === $request->user()->entreprise_id, 404);

        return response()->json($this->recherche->apercu($salarie));
    }
}