<?php

namespace App\Domain\Interim\Services;

/**
 * Saisies de paie issues des intérims pour une période, et anomalies à traiter par les RH.
 */
class RapportPaieInterims
{
    /** @var list<array{salarie_id:int, interim_id:int, rubrique_id:int, rubrique_nom:string, montant:int, observations:string}> */
    public array $saisies = [];

    /** @var list<array{salarie_id:int, interim_id:int, message:string}> */
    public array $anomalies = [];

    public function anomalie(int $salarieId, int $interimId, string $message): void
    {
        $this->anomalies[] = ['salarie_id' => $salarieId, 'interim_id' => $interimId, 'message' => $message];
    }
}
