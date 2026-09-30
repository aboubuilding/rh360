<?php

namespace App\Http\Controllers\Administration;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Requests\UpdateEntrepriseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class EntrepriseController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Entreprise::class);
        $entreprise = Entreprise::findOrFail(auth()->user()->entreprise_id);
        return view('administration.entreprise.index', compact('entreprise'));
    }

    public function edit()
    {
        $entreprise = Entreprise::findOrFail(auth()->user()->entreprise_id);
        $this->authorize('update', $entreprise);
        return view('administration.entreprise.edit', compact('entreprise'));
    }

    public function update(UpdateEntrepriseRequest $request)
    {
        $entreprise = Entreprise::findOrFail(auth()->user()->entreprise_id);
        $this->authorize('update', $entreprise);

        $donnees = $request->validated();

        if ($request->hasFile('logo')) {
            if ($entreprise->chemin_logo) {
                Storage::disk('local')->delete($entreprise->chemin_logo);
            }
            $donnees['chemin_logo'] = $request->file('logo')->store('entreprises/logos', 'local');
        }

        $entreprise->update($donnees);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Fiche entreprise mise à jour.',
                'redirect' => route('admin.entreprise.index'),
            ]);
        }

        return redirect()->route('admin.entreprise.index')
            ->with('success', 'Fiche entreprise mise à jour.');
    }

    /**
     * Sert le logo depuis le disque privé (jamais d'URL publique).
     */
    public function logo()
    {
        $entreprise = Entreprise::findOrFail(auth()->user()->entreprise_id);
        $this->authorize('view', $entreprise);

        abort_unless(
            $entreprise->chemin_logo && Storage::disk('local')->exists($entreprise->chemin_logo),
            404
        );

        return Storage::disk('local')->response($entreprise->chemin_logo);
    }
}