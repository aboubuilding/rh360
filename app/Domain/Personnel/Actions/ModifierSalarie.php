<?php

namespace App\Domain\Personnel\Actions;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Services\CalculateurCompletude;
use Illuminate\Support\Facades\DB;

class ModifierSalarie
{
    public function __construct(private CalculateurCompletude $completude) {}

    public function executer(Salarie $salarie, array $donnees): Salarie
    {
        return DB::transaction(function () use ($salarie, $donnees) {
            $salarie->update($donnees);
            $this->completude->mettreAJour($salarie->fresh());
            return $salarie->fresh();
        });
    }
}