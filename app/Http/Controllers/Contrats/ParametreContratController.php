<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Models\ParametreContrat;
use App\Domain\Contrats\Requests\UpdateParametreContratRequest;
use App\Http\Controllers\Controller;

class ParametreContratController extends Controller
{
    public function edit()
    {
        $this->authorize('permission', 'contrats.validate');

        $entrepriseId = auth()->user()->entreprise_id;
        $parametres = ParametreContrat::firstOrCreate(
            ['entreprise_id' => $entrepriseId],
            ['seuils' => '30,15,7,0', 'roles' => 'rh,drh', 'revision' => 1, 'etat' => 1],
        );

        return view('contrats.parametres.edit', compact('parametres'));
    }

    public function update(UpdateParametreContratRequest $request)
    {
        $this->authorize('permission', 'contrats.validate');

        $entrepriseId = auth()->user()->entreprise_id;
        $parametres = ParametreContrat::firstOrCreate(
            ['entreprise_id' => $entrepriseId],
            ['revision' => 1, 'etat' => 1],
        );

        $parametres->update([
            'seuils' => implode(',', $request->seuils),
            'roles' => implode(',', $request->roles),
            'revision' => $parametres->revision + 1,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Paramètres mis à jour.']);
        }

        return back()->with('success', 'Paramètres mis à jour.');
    }
}