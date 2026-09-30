<?php

namespace App\Http\Controllers\Administration;

use App\Domain\Administration\Actions\CreerUtilisateur;
use App\Domain\Administration\Actions\ModifierUtilisateur;
use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Administration\Requests\StoreUtilisateurRequest;
use App\Domain\Administration\Requests\UpdateUtilisateurRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UtilisateurController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Utilisateur::class);

        $utilisateurs = Utilisateur::query()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nom_complet', 'like', "%{$request->q}%")
                  ->orWhere('identifiant', 'like', "%{$request->q}%")
                  ->orWhere('email', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('etat'), fn ($q) => $q->where('etat', (int) $request->etat))
            ->orderBy('nom_complet')
            ->paginate(50)
            ->withQueryString();

        return view('administration.utilisateurs.index', compact('utilisateurs'));
    }

    public function create()
    {
        $this->authorize('create', Utilisateur::class);
        return view('administration.utilisateurs.create');
    }

    public function store(StoreUtilisateurRequest $request, CreerUtilisateur $action)
    {
        $this->authorize('create', Utilisateur::class);

        $utilisateur = $action->executer($request->validated());

        // Les formulaires (page et modale) soumettent en AJAX et attendent { message, redirect }
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Utilisateur créé avec succès.',
                'redirect' => route('admin.utilisateurs.show', $utilisateur),
            ], 201);
        }

        return redirect()->route('admin.utilisateurs.show', $utilisateur)
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(Utilisateur $utilisateur)
    {
        $this->authorize('view', $utilisateur);
        return view('administration.utilisateurs.show', compact('utilisateur'));
    }

    public function edit(Utilisateur $utilisateur)
    {
        $this->authorize('update', $utilisateur);
        return view('administration.utilisateurs.edit', compact('utilisateur'));
    }

    public function update(UpdateUtilisateurRequest $request, Utilisateur $utilisateur, ModifierUtilisateur $action)
    {
        $this->authorize('update', $utilisateur);

        $action->executer($utilisateur, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Utilisateur mis à jour.',
                'redirect' => route('admin.utilisateurs.show', $utilisateur),
            ]);
        }

        return redirect()->route('admin.utilisateurs.show', $utilisateur)
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function toggleActif(Utilisateur $utilisateur)
    {
        $this->authorize('update', $utilisateur);

        // « actif » est le verrou de connexion géré par l'administrateur ;
        // « etat » reste réservé à la suppression logique.
        $utilisateur->update(['actif' => ! $utilisateur->actif]);

        return back()->with('success', 'Statut de l\'utilisateur modifié.');
    }

    public function destroy(Utilisateur $utilisateur)
    {
        $this->authorize('delete', $utilisateur);
        $utilisateur->marquerSupprime();
        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur supprimé.');
    }
}