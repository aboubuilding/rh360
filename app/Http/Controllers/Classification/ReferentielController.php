<?php

namespace App\Http\Controllers\Classification;

use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Requests\StoreReferentielRequest;
use App\Http\Controllers\Controller;

class ReferentielController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ReferentielClassification::class);
        $referentiels = ReferentielClassification::orderByDesc('priorite')->paginate(50);
        return view('classification.referentiels.index', compact('referentiels'));
    }

    public function create()
    {
        $this->authorize('create', ReferentielClassification::class);
        return view('classification.referentiels.create');
    }

    public function store(StoreReferentielRequest $request)
    {
        $this->authorize('create', ReferentielClassification::class);
        ReferentielClassification::create($request->validated());
        return redirect()->route('classification.referentiels.index')
            ->with('success', 'Référentiel créé.');
    }

    public function show(ReferentielClassification $referentiel)
    {
        $this->authorize('view', $referentiel);
        $referentiel->load(['categories', 'classes', 'echelons', 'positions', 'reglesEvolution']);
        return view('classification.referentiels.show', compact('referentiel'));
    }

    public function edit(ReferentielClassification $referentiel)
    {
        $this->authorize('update', $referentiel);
        return view('classification.referentiels.edit', compact('referentiel'));
    }

    public function update(StoreReferentielRequest $request, ReferentielClassification $referentiel)
    {
        $this->authorize('update', $referentiel);
        $referentiel->update($request->validated());
        return redirect()->route('classification.referentiels.index')
            ->with('success', 'Référentiel mis à jour.');
    }

    public function destroy(ReferentielClassification $referentiel)
    {
        $this->authorize('delete', $referentiel);
        $referentiel->marquerSupprime();
        return redirect()->route('classification.referentiels.index')
            ->with('success', 'Référentiel supprimé.');
    }
}