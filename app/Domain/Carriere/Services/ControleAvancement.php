<?php

namespace App\Domain\Carriere\Services;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use Carbon\Carbon;

/**
 * Garde-fous des avancements, appliqués par AppliqueurMouvement à la prise d'effet.
 */
class ControleAvancement
{
    // Sous-types d'un acte de carrière.
    public const AVANCEMENT_CATEGORIE = 'avancement_categorie';        // CCIT art. 33 (fenêtre)

    public const PROMOTION_POSTE_VACANT = 'promotion_poste_vacant';    // CCIT art. 29

    public const REINTEGRATION_ESSAI = 'reintegration_essai';          // CCIT art. 29

    public const RETROGRADATION_DISCIPLINAIRE = 'retrogradation_disciplinaire'; // Code art. 119

    // CCIT art. 33 — motifs du changement de classe.
    public const MOTIFS_CHANGEMENT_CLASSE = [
        'qualification_superieure' => 'Accession à une qualification supérieure',
        'modification_poste' => 'Modification du poste de travail',
        'decision_discretionnaire' => 'Décision discrétionnaire du chef d\'établissement',
    ];

    public function __construct(
        private ParametresAvancement $parametres,
        private CalculateurEligibilite $eligibilite,
    ) {}

    /**
     * Notification écrite préalable de toute modification d'un élément du contrat (CCIT art. 12 ;
     * Code art. 71) : la prise d'effet est refusée sans date de notification.
     */
    public function verifierNotification(MouvementCarriere $mouvement): void
    {
        $this->parametres->pourEntreprise($mouvement->entreprise_id);

        if (($mouvement->estCarriere() || $mouvement->estAffectation())
            && $this->parametres->get('exiger_notification_prealable')
            && ! $mouvement->date_notification) {
            throw new \DomainException("Prise d'effet refusée : le mouvement {$mouvement->numero_mouvement} doit être notifié "
                .'par écrit au salarié avant sa prise d\'effet.');
        }
    }

    /**
     * Contrôles d'un changement de position de classification (avancement, promotion, reclassement).
     */
    public function verifierPosition(MouvementCarriere $mouvement, ?PositionClassification $ancienne, ?PositionClassification $nouvelle): void
    {
        if (! $ancienne || ! $nouvelle) {
            return;
        }

        $this->parametres->pourEntreprise($mouvement->entreprise_id);
        $sousType = $mouvement->sous_type_mouvement;
        $memeReferentiel = $ancienne->referentiel_id === $nouvelle->referentiel_id;

        // RG-ECH-01 / RG-ECH-03 — avancement d'échelon : échelon immédiatement supérieur ; au mérite avec motif.
        if (in_array($mouvement->type_mouvement, [TypeMouvement::AVANCEMENT, TypeMouvement::AVANCEMENT_ANTICIPE], true)) {
            if ($ancienne->position_suivante_id && $nouvelle->id !== $ancienne->position_suivante_id) {
                throw new \DomainException('Avancement refusé : l\'avancement d\'échelon se fait vers l\'échelon immédiatement supérieur, sans saut.');
            }
            if ($mouvement->type_mouvement === TypeMouvement::AVANCEMENT_ANTICIPE && blank($mouvement->motif)) {
                throw new \DomainException('Avancement refusé : un avancement d\'échelon au mérite, avant l\'échéance, doit être motivé.');
            }
        }

        if (! $memeReferentiel || ! $ancienne->categorie || ! $nouvelle->categorie) {
            return;
        }

        // RG-CNX-07 — aucune baisse de catégorie ni d'échelon, hors réintégration après essai et
        // rétrogradation disciplinaire (procédure disciplinaire, pièce obligatoire).
        $baisse = $nouvelle->categorie->ordre < $ancienne->categorie->ordre
            || ($nouvelle->categorie_id === $ancienne->categorie_id && ($nouvelle->echelon?->ordre ?? 0) < ($ancienne->echelon?->ordre ?? 0));
        if ($baisse) {
            if ($sousType === self::REINTEGRATION_ESSAI) {
                return;
            }
            if ($sousType === self::RETROGRADATION_DISCIPLINAIRE && ($mouvement->chemin_justificatif || $mouvement->reference_acte)) {
                return;
            }

            throw new \DomainException('Mouvement refusé : une baisse de catégorie ou d\'échelon n\'est possible que pour la réintégration '
                .'après un essai de promotion non concluant, ou pour une rétrogradation disciplinaire avec la référence de la procédure.');
        }

        if ($nouvelle->categorie_id === $ancienne->categorie_id) {
            return;
        }

        // RG-CLS-01 — changement de classe : qualification supérieure, modification du poste ou décision discrétionnaire.
        if ($ancienne->classe_id && $nouvelle->classe_id && $ancienne->classe_id !== $nouvelle->classe_id) {
            if (! array_key_exists((string) $sousType, self::MOTIFS_CHANGEMENT_CLASSE)) {
                throw new \DomainException('Changement de classe refusé : il doit être motivé par une qualification supérieure, '
                    .'une modification du poste de travail ou une décision discrétionnaire du chef d\'établissement.');
            }

            return;
        }

        // RG-CAT-01 — catégorie immédiatement supérieure, sans saut, dans la même classe.
        $suivante = $nouvelle->categorie->newQuery()
            ->where('referentiel_id', $ancienne->referentiel_id)
            ->where('ordre', '>', $ancienne->categorie->ordre)
            ->when($ancienne->classe_id, fn ($q) => $q->whereHas('positions', fn ($p) => $p->where('classe_id', $ancienne->classe_id)))
            ->orderBy('ordre')
            ->first();
        if ($suivante && $nouvelle->categorie_id !== $suivante->id && $sousType !== self::PROMOTION_POSTE_VACANT) {
            throw new \DomainException("Avancement refusé : l'avancement de catégorie se fait vers la catégorie immédiatement supérieure ({$suivante->libelle}), sans saut.");
        }

        // RG-CAT-02 — avancement de catégorie dans la fenêtre (au moins / au plus après le dernier échelon).
        if ($sousType === self::AVANCEMENT_CATEGORIE) {
            $fenetre = $this->eligibilite->fenetreCategorie($mouvement->salarie);
            if ($fenetre && $mouvement->date_effet && Carbon::parse($mouvement->date_effet)->lt(Carbon::parse($fenetre['ouverture']))) {
                throw new \DomainException('Avancement de catégorie refusé : la fenêtre ne s\'ouvre que le '
                    .Carbon::parse($fenetre['ouverture'])->format('d/m/Y').'.');
            }
        }
    }

    /**
     * RG-CLA-02 / RG-CNX-07 — après un changement de position, le salaire de base est au moins le
     * minimum de la nouvelle position ; il n'est jamais baissé.
     */
    public function alignerSalaireMinimum(MouvementCarriere $mouvement, ?PositionClassification $nouvelle): ?int
    {
        if (! $nouvelle?->salaire_minimum) {
            return null;
        }

        $rubrique = RubriquePaie::withoutGlobalScopes()->where('entreprise_id', $mouvement->entreprise_id)->where('code', 'SAL-BASE')->first();
        if (! $rubrique) {
            return null;
        }

        $element = ElementPaieSalarie::withoutGlobalScopes()->firstOrNew(
            ['salarie_id' => $mouvement->salarie_id, 'rubrique_id' => $rubrique->id],
            ['entreprise_id' => $mouvement->entreprise_id, 'montant' => 0, 'actif' => true, 'etat' => 1],
        );

        if ((int) $element->montant >= $nouvelle->salaire_minimum) {
            return null;
        }

        $ancien = (int) $element->montant;
        $element->montant = $nouvelle->salaire_minimum;
        $element->save();

        app(ServiceAudit::class)->tracer('carriere.salaire_aligne_minimum', $mouvement, [
            'ancien_salaire_base' => $ancien,
            'nouveau_salaire_base' => $nouvelle->salaire_minimum,
        ]);

        return $nouvelle->salaire_minimum;
    }
}
