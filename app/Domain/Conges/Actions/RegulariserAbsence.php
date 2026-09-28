<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Models\Absence;
use Illuminate\Support\Facades\DB;

class RegulariserAbsence
{
    public function executer(Absence $absence, QualificationAbsence $qualification, string $decision): Absence
    {
        if (empty(trim($decision))) {
            throw new \DomainException('La décision motivée est obligatoire.');
        }

        return DB::transaction(function () use ($absence, $qualification, $decision) {
            // Le constat initial est conservé — on ajoute la décision
            $absence->update([
                'qualification' => $qualification->value,
                'decision_regularisation' => $decision,
            ]);

            return $absence->fresh();
        });
    }
}