<?php

namespace App\Domain\Formation\Actions;

use App\Domain\Formation\Models\Formation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreerFormation
{
    public function executer(array $donnees): Formation
    {
        /** @var User $user */
        $user = Auth::user();

        return DB::transaction(function () use ($donnees, $user) {
            $donnees['entreprise_id'] = $user->entreprise_id;
            $donnees['etat'] = 1;

            return Formation::create($donnees);
        });
    }
}