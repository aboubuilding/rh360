<?php

namespace App\Http\Controllers\Administration;

use App\Domain\Administration\Actions\ModifierPermissionRole;
use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\PermissionUtilisateur;
use App\Domain\Administration\Models\Utilisateur;
use Database\Seeders\PermissionSeeder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function matrice()
    {
        $this->authorize('gererPermissions', Utilisateur::class);

        $roles = Utilisateur::roles();
        $permissions = PermissionSeeder::catalogue();

        // Matrice [role][permission] => bool
        $matrice = [];
        foreach (array_keys($roles) as $role) {
            $matrice[$role] = PermissionRole::query()
                ->where('role', $role)
                ->where('autorise', true)
                ->pluck('permission')
                ->flip()
                ->map(fn () => true)
                ->all();
        }

        return view('administration.permissions.matrice', compact('roles', 'permissions', 'matrice'));
    }

    public function modifierMatrice(Request $request, ModifierPermissionRole $action)
    {
        $this->authorize('gererPermissions', Utilisateur::class);

        $donnees = $request->validate([
            'role' => ['required', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $toutesPermissions = PermissionSeeder::catalogue();

        // Construit la map [permission => bool] à partir des cases cochées
        $map = [];
        foreach ($toutesPermissions as $permission) {
            $map[$permission] = in_array($permission, $donnees['permissions'] ?? [], true);
        }

        $action->executer(auth()->user()->entreprise_id, $donnees['role'], $map);

        return back()->with('success', 'Matrice mise à jour pour le rôle ' . $donnees['role'] . '.');
    }

    public function exceptions(Utilisateur $utilisateur)
    {
        $this->authorize('gererPermissions', Utilisateur::class);
        $this->authorize('view', $utilisateur);

        $permissions = PermissionSeeder::catalogue();
        $exceptions = $utilisateur->permissionsIndividuelles->keyBy('permission');

        return view('administration.permissions.exceptions', compact('utilisateur', 'permissions', 'exceptions'));
    }

    public function modifierExceptions(Request $request, Utilisateur $utilisateur)
    {
        $this->authorize('gererPermissions', Utilisateur::class);

        $donnees = $request->validate([
            'accorder' => ['array'],
            'accorder.*' => ['string'],
            'retirer' => ['array'],
            'retirer.*' => ['string'],
        ]);

        PermissionUtilisateur::where('utilisateur_id', $utilisateur->id)->delete();

        foreach ($donnees['accorder'] ?? [] as $permission) {
            PermissionUtilisateur::create([
                'entreprise_id' => $utilisateur->entreprise_id,
                'utilisateur_id' => $utilisateur->id,
                'permission' => $permission,
                'autorise' => true,
            ]);
        }
        foreach ($donnees['retirer'] ?? [] as $permission) {
            PermissionUtilisateur::create([
                'entreprise_id' => $utilisateur->entreprise_id,
                'utilisateur_id' => $utilisateur->id,
                'permission' => $permission,
                'autorise' => false,
            ]);
        }

        app(\App\Domain\Administration\Services\ServicePermissions::class)->viderCache($utilisateur);

        return back()->with('success', 'Exceptions individuelles mises à jour.');
    }
}