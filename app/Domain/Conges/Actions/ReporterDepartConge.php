<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\HistoriqueDemande;
use App\Domain\Conges\Services\CalculateurDureeConge;
use App\Domain\Conges\Services\ControleDemande;
use App\Domain\Conges\Services\GrandLivreConges;
use App\Domain\Conges\Services\ParametresDemandes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Décalage d'un départ déjà fixé. Une fois fixée, la date de départ ne peut être ni avancée ni
 * retardée de plus de 3 mois, sauf cas de force majeure (CCIT art. 49 b). Le décalage se mesure
 * depuis la première date fixée.
 */
class ReporterDepartConge
{
    public function __construct(
        private CalculateurDureeConge $calculateurDuree,
        private ControleDemande $controle,
        private ParametresDemandes $parametres,
    ) {}

    public function executer(DemandeConge $demande, Carbon $debut, Carbon $reprise, string $motif, bool $forceMajeure = false): DemandeConge
    {
        if (! in_array($demande->statut, [StatutDemandeConge::AUTORISEE, StatutDemandeConge::PROGRAMMEE], true)) {
            throw new \DomainException('Seul un départ autorisé ou programmé, et pas encore commencé, peut être décalé.');
        }
        if (blank($motif)) {
            throw new \DomainException('Le motif du décalage est obligatoire.');
        }
        if (! $reprise->gt($debut)) {
            throw new \DomainException('La date de reprise doit être postérieure à la date de départ.');
        }

        $reference = ($demande->date_debut_fixee ?? $demande->date_debut)->copy()->startOfDay();
        $maxMois = (int) $this->parametres->pourEntreprise($demande->entreprise_id)->get('conge_decalage_max_mois');
        $horsLimite = $debut->lt($reference->copy()->subMonthsNoOverflow($maxMois)) || $debut->gt($reference->copy()->addMonthsNoOverflow($maxMois));
        if ($horsLimite && ! $forceMajeure) {
            throw new \DomainException(sprintf(
                'Une date de départ fixée ne peut être décalée de plus de %d mois (entre le %s et le %s), sauf force majeure.',
                $maxMois,
                $reference->copy()->subMonthsNoOverflow($maxMois)->format('d/m/Y'),
                $reference->copy()->addMonthsNoOverflow($maxMois)->format('d/m/Y'),
            ));
        }

        return DB::transaction(function () use ($demande, $debut, $reprise, $motif, $forceMajeure, $reference) {
            $avant = $demande->date_debut->format('d/m/Y');
            $demande->fill([
                'date_debut' => $debut,
                'date_reprise' => $reprise,
                'duree_jours' => $this->calculateurDuree->calculer($debut, $reprise, $demande->typeConge),
                'date_debut_fixee' => $reference,
                // L'ordre de départ est à communiquer à nouveau pour la nouvelle date.
                'ordre_depart_le' => null,
            ]);
            $this->controle->exiger($demande);
            $demande->save();

            // Les jours sont imputés à nouveau, selon les lots ouverts à la nouvelle date.
            if (GrandLivreConges::gere($demande->typeConge)) {
                $grandLivre = app(GrandLivreConges::class);
                $grandLivre->recrediter($demande, 'Départ décalé');
                $grandLivre->imputerPrise($demande->fresh());
                $grandLivre->projeter($demande->salarie, $demande->typeConge);
            }

            HistoriqueDemande::create([
                'entreprise_id' => $demande->entreprise_id,
                'demande_id' => $demande->id,
                'de_statut' => $demande->statut->value,
                'vers_statut' => $demande->statut->value,
                'acteur_id' => auth()->id(),
                'motif' => "Départ décalé du {$avant} au {$debut->format('d/m/Y')}".($forceMajeure ? ' (force majeure)' : '')." : {$motif}",
            ]);

            return $demande->fresh();
        });
    }
}
