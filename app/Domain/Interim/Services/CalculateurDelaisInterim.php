<?php

namespace App\Domain\Interim\Services;

use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Models\InterimInterne;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Calcule les plafonds de durée d'un intérim interne (RG-INT-03, RG-INT-05).
 */
class CalculateurDelaisInterim
{
    public function __construct(private ParametresInterim $parametres) {}

    // Mode « date_a_date » (calendaire) : du 19/09 au 19/11 pour deux mois.
    public function ajouterMois(CarbonInterface $date, int $mois): Carbon
    {
        return Carbon::parse($date)->startOfDay()->addMonthsNoOverflow($mois);
    }

    /**
     * RG-INT-03 — Jours déjà effectués par les intérims successifs (sans interruption)
     * sur le même poste et pour le même titulaire, si le paramètre de cumul est actif.
     */
    public function joursDejaEffectues(int $posteId, ?int $titulaireId, CarbonInterface $debut, ?int $exclureId = null): int
    {
        if (! $this->parametres->get('cumul_interims_meme_poste')) {
            return 0;
        }

        $precedents = InterimInterne::query()
            ->where('poste_occupe_id', $posteId)
            ->when($titulaireId, fn ($q) => $q->where('titulaire_id', $titulaireId), fn ($q) => $q->whereNull('titulaire_id'))
            ->when($exclureId, fn ($q) => $q->where('id', '!=', $exclureId))
            ->where('date_debut', '<', $debut)
            ->orderByDesc('date_debut')
            ->get();

        $jours = 0;
        $debutChaine = Carbon::parse($debut)->startOfDay();

        foreach ($precedents as $precedent) {
            $fin = ($precedent->date_fin_reelle ?? $precedent->date_fin_prevue)->copy()->startOfDay();

            // Une interruption d'au moins un jour rompt la succession.
            if ($fin->lt($debutChaine->copy()->subDay())) {
                break;
            }

            $finRetenue = $fin->gt($debutChaine) ? $debutChaine : $fin;
            $jours += (int) $precedent->date_debut->diffInDays($finRetenue);
            $debutChaine = $precedent->date_debut->copy()->startOfDay();
        }

        return $jours;
    }

    // RG-INT-03 — Date de fin au plus tard sans motif éligible.
    public function plafondNormal(CarbonInterface $debut, int $joursDejaEffectues = 0): Carbon
    {
        return $this->ajouterMois($debut, (int) $this->parametres->get('duree_max_interim_mois'))
            ->subDays($joursDejaEffectues);
    }

    /**
     * RG-INT-05 — Plafond de la prolongation selon le délai légal du motif.
     * Retourne null lorsque la date n'est pas encore connue (fin de congé non saisie,
     * consolidation non connue).
     */
    public function plafondLegal(
        MotifInterim $motif,
        CarbonInterface $debut,
        ?CarbonInterface $dateFinMotif = null,
        ?Absence $absenceTitulaire = null,
        int $joursDejaEffectues = 0,
    ): ?Carbon {
        $delai = $this->delaiLegal($motif);
        if ($delai === null) {
            return null;
        }

        if (is_int($delai)) {
            // Par défaut, le délai est celui de la suspension du contrat du titulaire : il court
            // depuis le début de son absence lorsqu'elle est connue, sinon depuis le début
            // de la chaîne d'intérims (paramètre origine_delai_legal).
            $debutChaine = Carbon::parse($debut)->subDays($joursDejaEffectues);
            $origine = $this->parametres->get('origine_delai_legal') === 'debut_absence_titulaire'
                ? ($absenceTitulaire?->debut_le ?? $debutChaine)
                : $debutChaine;

            return $this->ajouterMois($origine, $delai);
        }

        return match ($delai) {
            'fin_conge' => $dateFinMotif
                ? Carbon::parse($dateFinMotif)->startOfDay()
                : ($absenceTitulaire?->fin_le ? Carbon::parse($absenceTitulaire->fin_le)->startOfDay() : null),
            'date_consolidation' => $dateFinMotif ? Carbon::parse($dateFinMotif)->startOfDay() : null,
            default => null,
        };
    }

    /**
     * RG-INT-05 — Délai légal du motif : un nombre de mois paramétré, ou « fin_conge » /
     * « date_consolidation » lorsque le plafond est une date saisie. Null si le motif n'est pas éligible.
     */
    public function delaiLegal(MotifInterim $motif): int|string|null
    {
        return match ($motif->cleDelaiLegal()) {
            'maladie' => (int) $this->parametres->get('delai_maladie_mois'),
            'detention_preventive' => (int) $this->parametres->get('delai_detention_preventive_mois'),
            'conges' => config('interim.delais_par_date.conges'),
            'at_mp' => config('interim.delais_par_date.at_mp'),
            default => null,
        };
    }
}
