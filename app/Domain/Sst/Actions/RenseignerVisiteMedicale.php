<?php

namespace App\Domain\Sst\Actions;

use App\Domain\Sst\Enums\AptitudeMedicale;
use App\Domain\Sst\Enums\StatutVisiteMedicale;
use App\Domain\Sst\Enums\TypeVisiteMedicale;
use App\Domain\Sst\Models\VisiteMedicale;
use App\Domain\Sst\Services\CalculateurEcheanceVisite;
use Illuminate\Support\Facades\DB;

class RenseignerVisiteMedicale
{
    public function __construct(private CalculateurEcheanceVisite $calcEcheance) {}

    public function executer(
        VisiteMedicale $visite,
        AptitudeMedicale $aptitude,
        string $dateRealisation,
        ?string $referenceAvis = null,
        ?string $restrictions = null,
        ?string $prestataire = null,
    ): VisiteMedicale {
        if ($visite->statut === StatutVisiteMedicale::REALISEE) {
            throw new \DomainException('Cette visite a déjà été renseignée.');
        }

        return DB::transaction(function () use ($visite, $aptitude, $dateRealisation, $referenceAvis, $restrictions, $prestataire) {
            $dateReal = \Carbon\Carbon::parse($dateRealisation);

            // Calculer la prochaine échéance si visite périodique
            $prochaine = null;
            if ($visite->type_visite === TypeVisiteMedicale::PERIODIQUE) {
                $prochaine = $this->calcEcheance->calculerProchaine(
                    $visite->salarie,
                    $visite->type_visite,
                    $dateReal,
                );
            }

            $visite->update([
                'statut' => StatutVisiteMedicale::REALISEE->value,
                'date_realisation' => $dateReal,
                'aptitude' => $aptitude->value,
                'reference_avis' => $referenceAvis,
                'restrictions' => $restrictions,
                'prestataire' => $prestataire,
                'date_prochaine_echeance' => $prochaine,
                'revision' => $visite->revision + 1,
                'modifie_par' => auth()->id(),
            ]);

            return $visite->fresh();
        });
    }

    public function annuler(VisiteMedicale $visite, string $motif): VisiteMedicale
    {
        if (empty(trim($motif))) {
            throw new \DomainException('Le motif d\'annulation est obligatoire.');
        }

        $visite->update([
            'statut' => StatutVisiteMedicale::ANNULEE->value,
            'motif_annulation' => $motif,
            'revision' => $visite->revision + 1,
            'modifie_par' => auth()->id(),
        ]);

        return $visite->fresh();
    }
}