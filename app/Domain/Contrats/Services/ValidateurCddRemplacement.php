<?php

namespace App\Domain\Contrats\Services;

use App\Domain\Conges\Models\Absence;
use App\Domain\Contrats\Enums\MotifRecoursCdd;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Interim\Services\CalculateurDelaisInterim;
use App\Domain\Interim\Services\ParametresInterim;
use Carbon\Carbon;

/**
 * Règles du CDD, notamment de remplacement (RG-REM-01 à RG-REM-05) — Code du travail, art. 48, 49 ; CCIT, art. 16.
 */
class ValidateurCddRemplacement
{
    public function __construct(
        private CalculateurDelaisInterim $delais,
        private ParametresInterim $parametres,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function controler(array $donnees, ?Contrat $parent = null): array
    {
        if (($donnees['type_contrat'] ?? null) !== TypeContrat::CDD->value) {
            return [];
        }

        $erreurs = [];
        $motif = ! empty($donnees['motif_recours']) ? MotifRecoursCdd::from($donnees['motif_recours']) : null;
        $titulaireId = $donnees['titulaire_remplace_id'] ?? null;
        $debut = Carbon::parse($donnees['date_debut'])->startOfDay();
        $fin = ! empty($donnees['date_fin']) ? Carbon::parse($donnees['date_fin'])->startOfDay() : null;
        $terme = $donnees['terme_evenement'] ?? null;

        // RG-REM-01 — le titulaire remplacé est obligatoire sur un CDD de remplacement.
        if ($motif?->estRemplacement() && ! $titulaireId) {
            $erreurs['titulaire_remplace_id'][] = 'Le salarié remplacé est obligatoire sur un CDD de remplacement.';
        }
        if ($titulaireId && isset($donnees['salarie_id']) && (int) $titulaireId === (int) $donnees['salarie_id']) {
            $erreurs['titulaire_remplace_id'][] = 'Le salarié remplacé ne peut pas être le titulaire du contrat.';
        }

        // RG-REM-02 — terme fixé par une date, ou par un événement pour un CDD de remplacement.
        if ($terme && ! $motif?->estRemplacement()) {
            $erreurs['terme_evenement'][] = 'Un terme fixé par un événement n\'est possible que pour un CDD de remplacement.';
        }
        if (! $fin && ! $terme) {
            $erreurs['date_fin'][] = 'Un CDD a un terme : une date de fin, ou, pour un remplacement, le retour du titulaire ou la rupture '
                .'de son contrat.';
        }

        // RG-REM-03 — durée initiale ≤ 2 ans ; durée totale, renouvellements compris, ≤ 4 ans.
        if ($fin) {
            $racine = $this->racine($parent);
            $debutChaine = $racine?->date_debut ?? $debut;
            $initiale = (int) $this->parametres->get('cdd_duree_initiale_max_mois');
            $totale = (int) $this->parametres->get('cdd_duree_totale_max_mois');

            if (! $parent && $fin->gt($limite = $this->delais->finDuree($debut, $initiale))) {
                $erreurs['date_fin'][] = 'La durée initiale d\'un CDD est limitée à '.$this->duree($initiale)
                    .", soit une fin au plus tard le {$limite->format('d/m/Y')}.";
            }
            if ($fin->gt($limite = $this->delais->finDuree($debutChaine, $totale))) {
                $erreurs['date_fin'][] = 'La durée totale d\'un CDD, renouvellements compris, est limitée à '.$this->duree($totale)
                    .", soit une fin au plus tard le {$limite->format('d/m/Y')}.";
            }
        }

        // RG-REM-05 — remplacement d'un malade : caractère provisoire de l'emploi notifié par écrit.
        if ($motif?->estRemplacement() && $titulaireId && $this->titulaireMalade((int) $titulaireId, $debut)
            && blank($donnees['mention_caractere_provisoire'] ?? null)) {
            $erreurs['mention_caractere_provisoire'][] = 'Le salarié recruté pour remplacer un salarié malade doit être informé par écrit '
                .'du caractère provisoire de son emploi ; cette mention figure sur la lettre d\'engagement.';
        }

        return $erreurs;
    }

    public function titulaireMalade(int $titulaireId, Carbon $date): bool
    {
        return Absence::where('salarie_id', $titulaireId)
            ->whereHas('typeConge', fn ($q) => $q->whereIn('code', (array) $this->parametres->get('codes_conges_maladie')))
            ->where('debut_le', '<=', $date->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('fin_le')->orWhere('fin_le', '>=', $date))
            ->exists();
    }

    private function racine(?Contrat $contrat): ?Contrat
    {
        while ($contrat?->parent_id) {
            $contrat = Contrat::find($contrat->parent_id) ?? $contrat;
            if (! $contrat->parent_id) {
                break;
            }
        }

        return $contrat;
    }

    private function duree(int $mois): string
    {
        return $mois % 12 === 0 ? ($mois / 12).' ans' : "{$mois} mois";
    }
}
