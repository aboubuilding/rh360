<?php

namespace App\Domain\Interim\Services;

use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Enums\MotifMaintienRemuneration;
use App\Domain\Interim\Models\MaintienRemuneration;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Personnel\Models\Affectation;

/**
 * RG-INF-01 à 03 — contrôles appliqués par AppliqueurMouvement au moment où un mouvement prend effet.
 */
class ControleEmploiInferieur
{
    // Sous-type d'une mutation décidée en raison de l'état de grossesse de la salariée (RG-INF-03).
    public const SOUS_TYPE_GROSSESSE = 'mutation_etat_grossesse';

    public function __construct(private ComparateurCategories $comparateur) {}

    /**
     * Qualifie l'affectation ou la mutation et vérifie la consultation des délégués pour une affectation
     * définitive à un poste inférieur (RG-INF-02). Retourne le motif de maintien, ou null.
     */
    public function qualifier(MouvementCarriere $mouvement, ?Affectation $courante): ?MotifMaintienRemuneration
    {
        if ($mouvement->sous_type_mouvement === self::SOUS_TYPE_GROSSESSE) {
            return MotifMaintienRemuneration::MUTATION_ETAT_GROSSESSE;
        }

        $origine = $courante ? Poste::with('categorieClassification')->find($courante->poste_id)?->categorieClassification : null;
        $cible = $mouvement->poste_cible_id ? Poste::with('categorieClassification')->find($mouvement->poste_cible_id)?->categorieClassification : null;

        if (! $origine || ! $cible || $origine->referentiel_id !== $cible->referentiel_id || $cible->ordre >= $origine->ordre) {
            return null;
        }

        // RG-INF-01 — à titre provisoire (date de fin prévue) ; sinon définitive (RG-INF-02).
        if ($mouvement->date_fin_prevue) {
            return MotifMaintienRemuneration::AFFECTATION_PROVISOIRE_INFERIEURE;
        }

        if (! $mouvement->date_consultation_delegues || ! $mouvement->chemin_pv_consultation) {
            throw new \DomainException("Affectation refusée : l'affectation définitive à un poste inférieur pour nécessité de service "
                .'exige la consultation préalable des délégués du personnel, avec sa date et le procès-verbal joint.');
        }

        return MotifMaintienRemuneration::AFFECTATION_DEFINITIVE_INFERIEURE;
    }

    /**
     * Ouvre le maintien de salaire et de classement à la date d'effet. Une nouvelle affectation met fin
     * aux maintiens temporaires en cours (affectation provisoire, mutation pour grossesse).
     */
    public function ouvrirMaintien(MouvementCarriere $mouvement, ?MotifMaintienRemuneration $motif): void
    {
        MaintienRemuneration::withoutGlobalScopes()
            ->where('salarie_id', $mouvement->salarie_id)
            ->whereIn('motif', [MotifMaintienRemuneration::AFFECTATION_PROVISOIRE_INFERIEURE->value, MotifMaintienRemuneration::MUTATION_ETAT_GROSSESSE->value])
            ->whereNull('date_fin')
            ->update(['date_fin' => $mouvement->date_effet->copy()->subDay()]);

        if (! $motif) {
            return;
        }

        $salaire = ElementPaieSalarie::withoutGlobalScopes()->where('salarie_id', $mouvement->salarie_id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->withoutGlobalScopes()->where('code', 'SAL-BASE'))->value('montant');

        MaintienRemuneration::create([
            'entreprise_id' => $mouvement->entreprise_id,
            'salarie_id' => $mouvement->salarie_id,
            'mouvement_carriere_id' => $mouvement->id,
            'motif' => $motif->value,
            'salaire_base_maintenu' => $salaire === null ? null : (int) $salaire,
            'position_maintenue_id' => $motif->maintientClassement()
                ? $this->comparateur->positionDuSalarie($mouvement->salarie)?->id
                : null,
            'date_debut' => $mouvement->date_effet,
            'date_fin' => $mouvement->date_fin_prevue,
        ]);
    }

    /**
     * RG-INF-01 / RG-INF-02 — le classement est maintenu : refus d'une position de catégorie inférieure
     * pendant un maintien en vigueur.
     */
    public function verifierClassement(MouvementCarriere $mouvement, ?PositionClassification $nouvelle): void
    {
        if (! $nouvelle?->categorie) {
            return;
        }

        $maintiens = MaintienRemuneration::withoutGlobalScopes()
            ->where('salarie_id', $mouvement->salarie_id)
            ->whereNotNull('position_maintenue_id')
            ->enVigueurLe($mouvement->date_effet ?? today())
            ->with('positionMaintenue.categorie')
            ->get();

        foreach ($maintiens as $maintien) {
            $maintenue = $maintien->positionMaintenue?->categorie;
            if ($maintenue && $maintenue->referentiel_id === $nouvelle->categorie->referentiel_id
                && $nouvelle->categorie->ordre < $maintenue->ordre) {
                throw new \DomainException("Mouvement refusé : le classement est maintenu pendant la {$maintien->motif->libelle()} "
                    ."({$maintenue->libelle}).");
            }
        }
    }
}
