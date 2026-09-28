<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Models\Absence;

class QualifierAbsence
{
    public function executer(Absence $absence, QualificationAbsence $qualification, ?string $decision = null): Absence
    {
        $absence->update([
            'qualification' => $qualification->value,
            'decision_regularisation' => $decision,
        ]);

        return $absence->fresh();
    }
}