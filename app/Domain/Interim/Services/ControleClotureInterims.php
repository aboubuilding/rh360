<?php

namespace App\Domain\Interim\Services;

use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Models\PeriodePaie;
use Illuminate\Support\Collection;

/**
 * RG-INT-09 — Un intérim à régulariser bloque la validation de la paie du mois
 * (paramètre bloquer_cloture_paie_si_a_regulariser).
 */
class ControleClotureInterims
{
    public function __construct(private ParametresInterim $parametres) {}

    /** @return Collection<int, InterimInterne> */
    public function interimsBloquants(PeriodePaie $periode): Collection
    {
        $this->parametres->pourEntreprise($periode->entreprise_id);

        if (! $this->parametres->get('bloquer_cloture_paie_si_a_regulariser')) {
            return collect();
        }

        return InterimInterne::withoutGlobalScopes()
            ->where('entreprise_id', $periode->entreprise_id)
            ->where('etat', 1)
            ->aRegulariser()
            ->with(['salarie', 'posteOccupe'])
            ->get();
    }

    public function verifier(PeriodePaie $periode): void
    {
        $bloquants = $this->interimsBloquants($periode);

        if ($bloquants->isEmpty()) {
            return;
        }

        $liste = $bloquants
            ->map(fn ($i) => $i->salarie?->nom_complet.' ('.$i->posteOccupe?->intitule.', délai expiré le '.$i->date_limite->format('d/m/Y').')')
            ->join(' ; ');

        throw new \DomainException(
            "Validation de la paie bloquée : {$bloquants->count()} intérim(s) à régulariser sans décision de "
            ."reclassement ou de retour — {$liste}."
        );
    }
}
