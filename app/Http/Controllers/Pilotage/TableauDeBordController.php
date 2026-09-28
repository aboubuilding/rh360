<?php

namespace App\Http\Controllers\Pilotage;

use App\Domain\Pilotage\Services\AgregateurTableauDeBord;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TableauDeBordController extends Controller
{
    public function __construct(private AgregateurTableauDeBord $agregateur) {}

    public function index(Request $request)
    {
        $this->authorize('view', \App\Domain\Pilotage\Policies\TableauDeBordPolicy::class);

        $tableauDeBord = $this->agregateur->pourUtilisateur($request->user());

        return view('pilotage.dashboard', [
            'tdb' => $tableauDeBord,
        ]);
    }
}