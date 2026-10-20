<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Contrats\Enums\MotifFinContrat;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Interim\Services\CalculateurDelaisInterim;
use App\Domain\Interim\Services\ParametresInterim;
use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * RG-REM-04 — Indemnité de fin de contrat après 4 ans consécutifs de CDD sans CDI : un mois du
 * dernier salaire brut, payé avec le dernier salaire (Code du travail, art. 54, 55).
 */
class IndemniteFinCddCalculator
{
    public function __construct(
        private CalculateurDelaisInterim $delais,
        private ParametresInterim $parametres,
    ) {}

    /**
     * CDD signés consécutifs (sans interruption) du salarié, qui se terminent par ce contrat.
     *
     * @return Collection<int, Contrat>
     */
    public function chaine(Contrat $contrat): Collection
    {
        $cdd = Contrat::withoutGlobalScopes()
            ->where('salarie_id', $contrat->salarie_id)
            ->where('type_contrat', TypeContrat::CDD->value)
            ->where('statut', StatutContrat::SIGNE->value)
            ->where('date_debut', '<=', $contrat->date_debut)
            ->orderByDesc('date_debut')
            ->get();

        $chaine = collect([$contrat]);
        $debut = $contrat->date_debut;

        foreach ($cdd as $precedent) {
            if ($precedent->id === $contrat->id || ! $precedent->date_fin) {
                continue;
            }
            if ($precedent->date_fin->lt($debut->copy()->subDay())) {
                break;
            }
            $chaine->prepend($precedent);
            $debut = $precedent->date_debut->min($debut);
        }

        return $chaine;
    }

    /**
     * @return array{due: bool, raison: string, debut_chaine: ?Carbon}
     */
    public function evaluer(Contrat $contrat): array
    {
        if ($contrat->type_contrat !== TypeContrat::CDD->value || ! $contrat->date_fin) {
            return ['due' => false, 'raison' => 'Contrat sans terme daté ou qui n\'est pas un CDD.', 'debut_chaine' => null];
        }

        $debutChaine = $this->chaine($contrat)->min('date_debut');
        $mois = (int) $this->parametres->get('cdd_anciennete_indemnite_mois');

        if ($contrat->date_fin->lt($this->delais->finDuree($debutChaine, $mois))) {
            return ['due' => false, 'raison' => "Moins de {$mois} mois de CDD consécutifs.", 'debut_chaine' => $debutChaine];
        }

        // Exclusions : embauche en CDI, refus d'un CDI au moins équivalent, fin pour motif économique.
        $cdi = Contrat::withoutGlobalScopes()
            ->where('salarie_id', $contrat->salarie_id)
            ->where('type_contrat', TypeContrat::CDI->value)
            ->whereIn('statut', [StatutContrat::VALIDE->value, StatutContrat::SIGNE->value])
            ->where('date_debut', '>=', $debutChaine)
            ->exists();
        if ($cdi) {
            return ['due' => false, 'raison' => 'Embauche en CDI.', 'debut_chaine' => $debutChaine];
        }
        if ($contrat->refus_cdi_equivalent_le) {
            return ['due' => false, 'raison' => 'Refus d\'un CDI au moins équivalent.', 'debut_chaine' => $debutChaine];
        }
        if ($contrat->motif_fin === MotifFinContrat::MOTIF_ECONOMIQUE) {
            return ['due' => false, 'raison' => 'Fin pour motif économique.', 'debut_chaine' => $debutChaine];
        }

        return ['due' => true, 'raison' => "{$mois} mois de CDD consécutifs sans CDI depuis le {$debutChaine->format('d/m/Y')}.", 'debut_chaine' => $debutChaine];
    }

    // Dernier salaire brut : brut du bulletin le plus récent avant la période de paie du dernier salaire.
    public function dernierSalaireBrut(Contrat $contrat, PeriodePaie $periode): ?int
    {
        $brut = BulletinPaie::withoutGlobalScopes()
            ->where('salarie_id', $contrat->salarie_id)
            ->whereHas('periode', fn ($q) => $q->withoutGlobalScopes()
                ->where(fn ($q) => $q->where('annee', '<', $periode->annee)
                    ->orWhere(fn ($q) => $q->where('annee', $periode->annee)->where('mois', '<', $periode->mois))))
            ->join('periodes_paie', 'periodes_paie.id', '=', 'bulletins_paie.periode_id')
            ->orderByDesc('periodes_paie.annee')->orderByDesc('periodes_paie.mois')
            ->value('bulletins_paie.montant_brut');

        return $brut === null ? null : (int) round((float) $brut);
    }
}
