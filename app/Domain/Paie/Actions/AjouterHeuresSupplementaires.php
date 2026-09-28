<?php

namespace App\Domain\Paie\Actions;

use App\Domain\Paie\Events\HeuresSuppAjoutees;
use App\Domain\Paie\Models\HeureSupplementaire;
use App\Domain\Paie\Services\CalculateurHeuresSupp;
use App\Domain\Paie\Models\ElementPaieSalarie;

class AjouterHeuresSupplementaires
{
    public function __construct(private CalculateurHeuresSupp $calculateur) {}

    public function executer(array $donnees): HeureSupplementaire
    {
        // Récupérer le salaire de base du salarié
        $element = ElementPaieSalarie::where('salarie_id', $donnees['salarie_id'])
            ->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
            ->first();

        $salaireBase = $element ? (float) $element->montant : 0.0;

        $acte = $this->calculateur->creer(
            auth()->user()->entreprise_id,
            $donnees['salarie_id'],
            $donnees,
            $salaireBase,
        );

        event(new HeuresSuppAjoutees($acte));

        return $acte;
    }
}