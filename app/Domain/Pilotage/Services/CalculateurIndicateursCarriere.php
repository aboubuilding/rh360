<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\CalculateurEligibilite;
use App\Domain\Pilotage\DTO\Indicateur;
use Illuminate\Support\Collection;

class CalculateurIndicateursCarriere
{
    public function __construct(private CalculateurEligibilite $eligibilite) {}

    public function calculer(int $entrepriseId): Collection
    {
        $aControler = MouvementCarriere::where('entreprise_id', $entrepriseId)
            ->whereIn('statut', [
                StatutMouvement::PROPOSE->value,
                StatutMouvement::A_VERIFIER->value,
            ])
            ->count();

        $aValider = MouvementCarriere::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutMouvement::VERIFIE->value)
            ->count();

        $programmes = MouvementCarriere::where('entreprise_id', $entrepriseId)
            ->where('statut', StatutMouvement::PROGRAMME->value)
            ->count();

        // Avancements échus
        $avancementsEchus = collect($this->eligibilite->echeancesProches($entrepriseId, 0))
            ->filter(fn ($e) => $e['est_eligible'])
            ->count();

        return collect([
            new Indicateur(
                cle: 'actes_a_controler',
                libelle: 'Actes à contrôler',
                valeur: $aControler,
                couleur: $aControler > 0 ? 'info' : 'success',
                icone: 'fa-clipboard-check',
                lien: route('carriere.mouvements.index', ['vue' => 'a_traiter']),
            ),
            new Indicateur(
                cle: 'actes_a_valider',
                libelle: 'Actes à valider',
                valeur: $aValider,
                couleur: $aValider > 0 ? 'warning' : 'success',
                icone: 'fa-check-double',
                lien: route('carriere.mouvements.index', ['statut' => StatutMouvement::VERIFIE->value]),
            ),
            new Indicateur(
                cle: 'actes_programmes',
                libelle: 'Actes programmés',
                valeur: $programmes,
                couleur: 'primary',
                icone: 'fa-calendar-alt',
                lien: route('carriere.mouvements.index', ['vue' => 'programmes']),
            ),
            new Indicateur(
                cle: 'avancements_echus',
                libelle: 'Avancements échus',
                valeur: $avancementsEchus,
                couleur: $avancementsEchus > 0 ? 'warning' : 'success',
                icone: 'fa-arrow-up',
                lien: route('carriere.avancements.index'),
            ),
        ]);
    }
}