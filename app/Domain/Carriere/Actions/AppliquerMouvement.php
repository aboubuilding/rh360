<?php

namespace App\Domain\Carriere\Actions;

use App\Domain\Carriere\Events\MouvementApplique;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;

class AppliquerMouvement
{
    public function __construct(private AppliqueurMouvement $appliqueur) {}

    public function executer(MouvementCarriere $mouvement): MouvementCarriere
    {
        $mouvement = $this->appliqueur->appliquer($mouvement);

        event(new MouvementApplique($mouvement));

        return $mouvement;
    }
}