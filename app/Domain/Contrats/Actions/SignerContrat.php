<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Events\ContratSigne;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\ReferenceurSignature;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;
use Carbon\Carbon;

class SignerContrat
{
    public function __construct(
        private ReferenceurSignature $referenceur,
        private GenerateurAlertesContrats $alertes,
    ) {}

    public function executer(Contrat $contrat, string $dateSignature, string $referenceSignee): Contrat
    {
        $contrat = $this->referenceur->referencer(
            $contrat,
            Carbon::parse($dateSignature),
            $referenceSignee,
        );

        $this->alertes->resynchroniser($contrat);

        event(new ContratSigne($contrat));

        return $contrat;
    }
}