<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreerUtilisateur
{
    public function executer(array $donnees): Utilisateur
    {
        return DB::transaction(function () use ($donnees) {
            $donnees['password'] = Hash::make($donnees['password']);
            return Utilisateur::create($donnees);
        });
    }
}