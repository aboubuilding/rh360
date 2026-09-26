<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Administration\Services\ServicePermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ModifierUtilisateur
{
    public function __construct(private ServicePermissions $permissions) {}

    public function executer(Utilisateur $utilisateur, array $donnees): Utilisateur
    {
        return DB::transaction(function () use ($utilisateur, $donnees) {
            if (! empty($donnees['password'])) {
                $donnees['password'] = Hash::make($donnees['password']);
            } else {
                unset($donnees['password']);
            }

            $utilisateur->update($donnees);

            // Invalider le cache de permissions
            $this->permissions->viderCache($utilisateur);

            return $utilisateur->fresh();
        });
    }
}