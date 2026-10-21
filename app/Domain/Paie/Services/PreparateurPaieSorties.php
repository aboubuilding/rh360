<?php

namespace App\Domain\Paie\Services;

use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Models\SaisiePaie;
use App\Domain\Paie\Models\SortieSalarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prépare, pour une période de paie non figée, les éléments du solde de tout compte des salariés
 * dont la cessation tombe dans le mois. Les saisies portent la source « sortie » et sont
 * recalculées à chaque calcul de la période.
 */
class PreparateurPaieSorties
{
    public const SOURCE = 'sortie';

    public function __construct(private CalculateurSoldeToutCompte $solde) {}

    /**
     * @return array<int, array{salarie_id: int, message: string}>
     */
    public function preparer(PeriodePaie $periode): array
    {
        if ($periode->estFigee()) {
            throw new \DomainException('Période validée : les éléments de sortie ne peuvent plus y être portés.');
        }

        $debut = Carbon::create($periode->annee, $periode->mois, 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth();
        $rubriques = RubriquePaie::pourEntreprise($periode->entreprise_id)->pluck('id', 'code');
        $alertes = [];

        DB::transaction(function () use ($periode, $debut, $fin, $rubriques, &$alertes) {
            SaisiePaie::where('periode_id', $periode->id)->where('source', self::SOURCE)->delete();

            $sorties = SortieSalarie::pourEntreprise($periode->entreprise_id)->with('salarie')
                ->whereBetween('date_cessation', [$debut->toDateString(), $fin->toDateString()])->get();

            foreach ($sorties as $sortie) {
                $calcul = $this->solde->calculer($sortie);
                foreach ($calcul['alertes'] as $message) {
                    $alertes[] = ['salarie_id' => $sortie->salarie_id, 'message' => $message];
                }
                foreach ($calcul['lignes'] as [$code, $libelle, $montant, $quantite]) {
                    if (! isset($rubriques[$code])) {
                        $alertes[] = ['salarie_id' => $sortie->salarie_id, 'message' => "Rubrique {$code} introuvable : « {$libelle} » n'a pas été portée sur le bulletin de sortie."];

                        continue;
                    }
                    (new SaisiePaie)->forceFill([
                        'entreprise_id' => $periode->entreprise_id,
                        'periode_id' => $periode->id,
                        'salarie_id' => $sortie->salarie_id,
                        'rubrique_id' => $rubriques[$code],
                        'quantite' => $quantite,
                        'taux' => 0,
                        'montant' => $montant,
                        'observations' => $libelle,
                        'source' => self::SOURCE,
                        'cree_par' => auth()->id(),
                        'etat' => 1,
                    ])->save();
                }
            }
        });

        return $alertes;
    }
}
