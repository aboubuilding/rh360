<?php

namespace App\Http\Controllers\Pilotage;

use App\Domain\Pilotage\Policies\TableauDeBordPolicy;
use App\Domain\Pilotage\Services\AgregateurTableauDeBord;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TableauDeBordController extends Controller
{
    public function __construct(private AgregateurTableauDeBord $agregateur) {}

    public function index(Request $request)
    {
        // Le tableau de bord n'est rattaché à aucun modèle : on interroge la policy directement.
        abort_unless(app(TableauDeBordPolicy::class)->view($request->user()), 403);

        $tableauDeBord = $this->agregateur->pourUtilisateur($request->user());

        return view('pilotage.dashboard', [
            'tdb' => $tableauDeBord,
        ]);
    }
}