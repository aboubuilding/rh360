<?php

namespace App\Http\Controllers\Organisation;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Requests\StorePosteRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PosteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Poste::class);
        $postes = Poste::query()
            ->with('structure')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('intitule', 'like', "%{$request->q}%")
                  ->orWhere('code', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('structure_id'), fn ($q) => $q->where('structure_id', $request->structure_id))
            ->orderBy('intitule')
            ->paginate(50)
            ->withQueryString();

        $structures = Structure::orderBy('nom')->get();
        return view('organisation.postes.index', compact('postes', 'structures'));
    }

    public function create()
    {
        $this->authorize('create', Poste::class);
        $structures = Structure::orderBy('nom')->get();
        return view('organisation.postes.create', compact('structures'));
    }

    public function store(StorePosteRequest $request)
    {
        $this->authorize('create', Poste::class);
        Poste::create($request->validated());
        return redirect()->route('organisation.postes.index')
            ->with('success', 'Poste créé.');
    }

    public function edit(Poste $poste)
    {
        $this->authorize('update', $poste);
        $structures = Structure::orderBy('nom')->get();
        return view('organisation.postes.edit', compact('poste', 'structures'));
    }

    public function update(StorePosteRequest $request, Poste $poste)
    {
        $this->authorize('update', $poste);
        $poste->update($request->validated());
        return redirect()->route('organisation.postes.index')
            ->with('success', 'Poste mis à jour.');
    }

    public function destroy(Poste $poste)
    {
        $this->authorize('delete', $poste);
        $poste->marquerSupprime();
        return redirect()->route('organisation.postes.index')
            ->with('success', 'Poste supprimé.');
    }
}