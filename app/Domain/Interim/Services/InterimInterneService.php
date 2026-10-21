<?php

namespace App\Domain\Interim\Services;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Carriere\Services\GenerateurNumeroMouvement;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Interim\Enums\CauseFinInterim;
use App\Domain\Interim\Enums\DecisionFinInterim;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Models\AvantageInterim;
use App\Domain\Interim\Models\DecisionInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Intérim interne d'un emploi supérieur (CCIT art. 13).
 */
class InterimInterneService
{
    public function __construct(
        private ValidateurInterimInterne $validateur,
        private ComparateurCategories $comparateur,
        private RepertoireBareme $bareme,
        private GestionnairePiecesInterim $pieces,
        private GenerateurNumeroMouvement $numeroteur,
        private AppliqueurMouvement $appliqueur,
        private ServiceAudit $audit,
    ) {}

    /**
     * RG-INT-01, RG-INT-03, RG-INT-04, RG-INT-05 — Création d'un intérim interne.
     * Le mouvement de carrière associé reste l'acte de l'historique de carrière.
     */
    public function creer(array $donnees, ?UploadedFile $piece = null): InterimInterne
    {
        $controle = $this->validateur->controler($donnees)->exigerValide();

        return DB::transaction(function () use ($donnees, $controle, $piece) {
            $salarie = Salarie::findOrFail($donnees['salarie_id']);
            $poste = Poste::findOrFail($donnees['poste_occupe_id']);
            $motif = MotifInterim::from($donnees['motif']);
            $debut = Carbon::parse($donnees['date_debut'])->startOfDay();
            $finPrevue = Carbon::parse($donnees['date_fin_prevue'])->startOfDay();

            $affectation = Affectation::where('salarie_id', $salarie->id)->where('en_cours', true)->latest('date_debut')->first();
            $position = $this->comparateur->positionDuSalarie($salarie);

            $mouvement = MouvementCarriere::create([
                'entreprise_id' => auth()->user()->entreprise_id,
                'numero_mouvement' => $this->numeroteur->generer(auth()->user()->entreprise_id),
                'salarie_id' => $salarie->id,
                'type_mouvement' => TypeMouvement::INTERIM->value,
                'motif' => trim($motif->libelle().' '.($donnees['precision_motif'] ?? '')),
                'statut' => $debut->lte(today()) ? StatutMouvement::EFFECTIF->value : StatutMouvement::PROGRAMME->value,
                'date_proposition' => now(),
                'date_effet' => $debut,
                'date_fin_prevue' => $finPrevue,
                'structure_depart_id' => $affectation?->structure_id,
                'poste_depart_id' => $affectation?->poste_id,
                'position_classification_depart_id' => $position?->id,
                'structure_cible_id' => $poste->structure_id,
                'poste_cible_id' => $poste->id,
                'type_source' => 'manual',
                'type_saisie_source' => 'normal',
                'cree_par' => auth()->id(),
                'etat' => 1,
            ]);

            $interim = InterimInterne::create([
                'entreprise_id' => auth()->user()->entreprise_id,
                'mouvement_carriere_id' => $mouvement->id,
                'salarie_id' => $salarie->id,
                'poste_occupe_id' => $poste->id,
                'categorie_occupee_id' => $controle->categorieOccupee->id,
                'categorie_origine_id' => $controle->categorieOrigine->id,
                'titulaire_id' => $donnees['titulaire_id'] ?? null,
                'absence_titulaire_id' => $donnees['absence_titulaire_id'] ?? null,
                'motif' => $motif->value,
                'precision_motif' => $donnees['precision_motif'] ?? null,
                'date_debut' => $debut,
                'date_fin_prevue' => $finPrevue,
                'date_fin_motif' => $donnees['date_fin_motif'] ?? null,
                'date_limite' => $controle->dateLimite,
                // RG-INT-02 — instantané de la situation d'origine, inchangée pendant l'intérim
                'salaire_base_origine' => $this->salaireBase($salarie),
                'position_origine_id' => $position?->id,
                'affectation_origine_id' => $affectation?->id,
                'statut' => $this->statutSelonDuree($finPrevue, $controle)->value,
                'cree_par' => auth()->id(),
            ]);

            $this->historiser($interim, TypeDecisionInterim::CREATION, [
                'date_debut' => $debut->toDateString(),
                'date_fin_prevue' => $finPrevue->toDateString(),
                'date_limite' => $controle->dateLimite->toDateString(),
                'jours_deja_effectues' => $controle->joursDejaEffectues,
            ], null, $piece);

            return $interim;
        });
    }

    /**
     * RG-INT-03, RG-INT-04, RG-INT-05 — Prolongation, dans la limite du délai légal du motif.
     */
    public function prolonger(
        InterimInterne $interim,
        string $nouvelleFin,
        ?string $dateFinMotif,
        UploadedFile $piece,
        ?string $commentaire = null,
    ): InterimInterne {
        $this->exigerNonClos($interim);

        $nouvelleFin = Carbon::parse($nouvelleFin)->startOfDay();
        if ($nouvelleFin->lte($interim->date_fin_prevue)) {
            throw ValidationException::withMessages([
                'date_fin_prevue' => 'La nouvelle date de fin doit être postérieure à la fin prévue actuelle ('
                    .$interim->date_fin_prevue->format('d/m/Y').').',
            ]);
        }

        $controle = $this->validateur->controler([
            'salarie_id' => $interim->salarie_id,
            'poste_occupe_id' => $interim->poste_occupe_id,
            'titulaire_id' => $interim->titulaire_id,
            'absence_titulaire_id' => $interim->absence_titulaire_id,
            'motif' => $interim->motif->value,
            'date_debut' => $interim->date_debut->toDateString(),
            'date_fin_prevue' => $nouvelleFin->toDateString(),
            'date_fin_motif' => $dateFinMotif ?? $interim->date_fin_motif?->toDateString(),
        ], $interim->id);

        // Une prolongation ne remet pas en cause un intérim déjà accepté : seuls les
        // contrôles de durée s'appliquent ici.
        $erreursDuree = array_intersect_key($controle->erreurs, array_flip(['date_fin_prevue', 'date_fin_motif']));
        if ($erreursDuree !== []) {
            throw ValidationException::withMessages($erreursDuree);
        }

        return DB::transaction(function () use ($interim, $nouvelleFin, $dateFinMotif, $controle, $piece, $commentaire) {
            $ancienneFin = $interim->date_fin_prevue->toDateString();

            $interim->update([
                'date_fin_prevue' => $nouvelleFin,
                'date_fin_motif' => $dateFinMotif ?? $interim->date_fin_motif,
                'date_limite' => $controle->dateLimite,
                'statut' => $this->statutSelonDuree($nouvelleFin, $controle)->value,
            ]);

            $interim->mouvementCarriere?->update(['date_fin_prevue' => $nouvelleFin]);

            $this->historiser($interim, TypeDecisionInterim::PROLONGATION, [
                'ancienne_fin' => $ancienneFin,
                'nouvelle_fin' => $nouvelleFin->toDateString(),
                'date_limite' => $controle->dateLimite->toDateString(),
            ], $commentaire, $piece);

            return $interim->fresh();
        });
    }

    /**
     * RG-INT-10 — Le retour du titulaire, la rupture de son contrat ou la fin du motif
     * clôturent l'intérim. Au-delà de la date limite, une décision est obligatoire (RG-INT-08).
     */
    public function cloturer(
        InterimInterne $interim,
        CauseFinInterim $cause,
        string $dateFin,
        ?string $commentaire = null,
        ?UploadedFile $piece = null,
    ): InterimInterne {
        $this->exigerNonClos($interim);

        if ($cause === CauseFinInterim::DECISION) {
            throw ValidationException::withMessages([
                'cause_fin' => 'Une fin par décision passe par le reclassement ou le retour.',
            ]);
        }

        $dateFin = Carbon::parse($dateFin)->startOfDay();
        $this->exigerDateFinValide($interim, $dateFin);

        if ($dateFin->gt($interim->date_limite)) {
            throw ValidationException::withMessages([
                'date_fin_reelle' => 'Clôture refusée : le délai de l\'intérim a expiré le '
                    .$interim->date_limite->format('d/m/Y').'. Une décision de reclassement ou de retour '
                    .'est obligatoire.',
            ]);
        }

        return DB::transaction(function () use ($interim, $cause, $dateFin, $commentaire, $piece) {
            $this->fermer($interim, $dateFin, $cause, null, $commentaire);

            $this->historiser($interim, TypeDecisionInterim::CLOTURE, [
                'cause' => $cause->value,
                'date_fin_reelle' => $dateFin->toDateString(),
            ], $commentaire, $piece);

            return $interim->fresh();
        });
    }

    /**
     * RG-INT-08 — Décision de fin : reclassement dans la catégorie occupée, ou retour
     * à l'emploi et à la situation d'origine. L'indemnité d'intérim prend fin dans les deux cas.
     */
    public function deciderFin(
        InterimInterne $interim,
        DecisionFinInterim $decision,
        string $dateEffet,
        UploadedFile $piece,
        ?int $positionCibleId = null,
        ?int $nouveauSalaireBase = null,
        ?string $commentaire = null,
    ): InterimInterne {
        $this->exigerNonClos($interim);

        $dateEffet = Carbon::parse($dateEffet)->startOfDay();
        $this->exigerDateFinValide($interim, $dateEffet);

        $position = null;
        if ($decision === DecisionFinInterim::RECLASSEMENT) {
            $position = $this->controlerReclassement($interim, $dateEffet, $positionCibleId, $nouveauSalaireBase);
        }

        return DB::transaction(function () use ($interim, $decision, $dateEffet, $piece, $position, $nouveauSalaireBase, $commentaire) {
            $donnees = ['date_effet' => $dateEffet->toDateString()];

            if ($decision === DecisionFinInterim::RECLASSEMENT) {
                $donnees += $this->appliquerReclassement($interim, $dateEffet, $position, $nouveauSalaireBase);
            }

            // RG-INT-08 retour : le poste permanent et la situation d'origine n'ont jamais
            // été modifiés par l'intérim (RG-INT-02) ; seule la fin de l'intérim est actée.
            $this->fermer($interim, $dateEffet, CauseFinInterim::DECISION, $decision, $commentaire);

            $this->historiser(
                $interim,
                $decision === DecisionFinInterim::RECLASSEMENT ? TypeDecisionInterim::RECLASSEMENT : TypeDecisionInterim::RETOUR,
                $donnees,
                $commentaire,
                $piece,
            );

            return $interim->fresh();
        });
    }

    /**
     * RG-INT-09 — Délai dépassé sans décision : l'intérim passe « à régulariser ».
     * Il n'est jamais clôturé automatiquement.
     */
    public function passerARegulariser(InterimInterne $interim, string $raison): InterimInterne
    {
        if ($interim->estClos() || $interim->statut === StatutInterim::A_REGULARISER) {
            return $interim;
        }

        $interim->update(['statut' => StatutInterim::A_REGULARISER->value]);
        $this->historiser($interim, TypeDecisionInterim::REGULARISATION, ['raison' => $raison], $raison, null, null);

        return $interim->fresh();
    }

    /**
     * RG-INT-07 — Avantage du poste occupé : jamais calculé, saisi à la main
     * avec la référence de l'accord entre les parties et son justificatif.
     */
    public function ajouterAvantage(InterimInterne $interim, array $donnees, UploadedFile $justificatif): AvantageInterim
    {
        $this->exigerNonClos($interim);

        $rubrique = RubriquePaie::findOrFail($donnees['rubrique_id']);
        if ($rubrique->code === config('interim.rubrique_indemnite') || $rubrique->code === 'SAL-BASE') {
            throw ValidationException::withMessages([
                'rubrique_id' => 'Cette rubrique est calculée par l\'application et ne peut pas être saisie comme avantage.',
            ]);
        }

        return DB::transaction(function () use ($interim, $donnees, $justificatif) {
            $piece = $this->pieces->enregistrer($justificatif, "internes/{$interim->id}/avantages");

            $avantage = $interim->avantages()->create([
                'entreprise_id' => $interim->entreprise_id,
                'rubrique_id' => $donnees['rubrique_id'],
                'montant_mensuel' => (int) $donnees['montant_mensuel'],
                'date_debut' => $donnees['date_debut'],
                'date_fin' => $donnees['date_fin'] ?? null,
                'reference_accord' => $donnees['reference_accord'],
                'chemin_justificatif' => $piece['chemin_piece'],
                'cree_par' => auth()->id(),
            ]);

            $this->historiser($interim, TypeDecisionInterim::AVANTAGE, [
                'avantage_id' => $avantage->id,
                'rubrique_id' => $avantage->rubrique_id,
                'montant_mensuel' => $avantage->montant_mensuel,
                'reference_accord' => $avantage->reference_accord,
            ], null, null, $piece);

            return $avantage;
        });
    }

    // --- Interne ---

    private function controlerReclassement(InterimInterne $interim, Carbon $dateEffet, ?int $positionCibleId, ?int $nouveauSalaire): PositionClassification
    {
        $position = $positionCibleId ? PositionClassification::find($positionCibleId) : null;
        if (! $position || $position->categorie_id !== $interim->categorie_occupee_id) {
            throw ValidationException::withMessages([
                'position_cible_id' => 'Reclassement refusé : la nouvelle position doit relever de la catégorie occupée '
                    .'pendant l\'intérim.',
            ]);
        }

        $minimums = array_filter([
            $position->salaire_minimum,
            $this->baremeSansAmbiguite($interim, $dateEffet),
        ], fn ($m) => $m !== null);

        if ($minimums === []) {
            throw ValidationException::withMessages([
                'position_cible_id' => 'Reclassement refusé : aucun salaire minimum n\'est défini pour la catégorie occupée.',
            ]);
        }

        $minimum = (int) max($minimums);
        if ($nouveauSalaire === null || $nouveauSalaire < $minimum) {
            throw ValidationException::withMessages([
                'nouveau_salaire_base' => 'Reclassement refusé : le nouveau salaire de base ('.number_format((int) $nouveauSalaire, 0, ',', ' ')
                    .' FCFA) est inférieur au minimum de la catégorie ('.number_format($minimum, 0, ',', ' ').' FCFA).',
            ]);
        }

        return $position;
    }

    private function baremeSansAmbiguite(InterimInterne $interim, Carbon $date): ?int
    {
        try {
            return $this->bareme->salaireMinimum($interim->categorieOccupee, $date);
        } catch (\DomainException) {
            return null;
        }
    }

    /**
     * RG-INT-08 reclassement — nouvelle catégorie, nouveau salaire de base, historisation dans la carrière.
     * À une date d'effet future, le reclassement est programmé et appliqué à sa date
     * (commande interims:appliquer-reclassements).
     *
     * @return array<string, mixed>
     */
    private function appliquerReclassement(InterimInterne $interim, Carbon $dateEffet, PositionClassification $position, int $nouveauSalaire): array
    {
        $situationPosition = $this->comparateur->positionDuSalarie($interim->salarie);

        $mouvement = MouvementCarriere::create([
            'entreprise_id' => $interim->entreprise_id,
            'numero_mouvement' => $this->numeroteur->generer($interim->entreprise_id),
            'salarie_id' => $interim->salarie_id,
            'type_mouvement' => TypeMouvement::RECLASSEMENT->value,
            'motif' => 'Reclassement à l\'issue de l\'intérim du poste '.$interim->posteOccupe?->intitule.'',
            'statut' => StatutMouvement::PROGRAMME->value,
            'date_proposition' => now(),
            'date_decision' => today(),
            // La décision de reclassement, avec sa pièce, vaut notification écrite au salarié.
            'date_notification' => today(),
            'date_effet' => $dateEffet,
            'position_classification_depart_id' => $situationPosition?->id,
            'position_classification_cible_id' => $position->id,
            'type_source' => 'manual',
            'type_saisie_source' => 'normal',
            'cree_par' => auth()->id(),
            'valide_par' => auth()->id(),
            'etat' => 1,
        ]);

        $interim->update([
            'reclassement_mouvement_id' => $mouvement->id,
            'reclassement_salaire_base' => $nouveauSalaire,
        ]);

        $ancienSalaire = $this->salaireBase($interim->salarie);
        if (! $dateEffet->isFuture()) {
            $this->appliquerReclassementProgramme($interim);
        }

        return [
            'mouvement_reclassement_id' => $mouvement->id,
            'position_cible_id' => $position->id,
            'ancien_salaire_base' => $ancienSalaire,
            'nouveau_salaire_base' => $nouveauSalaire,
            'programme' => $dateEffet->isFuture(),
        ];
    }

    /**
     * RG-INT-08 — Applique à sa date d'effet un reclassement décidé : position de classification
     * (via le mouvement de carrière) et nouveau salaire de base. Sans effet s'il est déjà appliqué
     * ou si sa date n'est pas atteinte.
     */
    public function appliquerReclassementProgramme(InterimInterne $interim): bool
    {
        $mouvement = $interim->reclassement_mouvement_id
            ? MouvementCarriere::find($interim->reclassement_mouvement_id)
            : null;

        if (! $mouvement || $interim->reclassement_applique_le || $mouvement->date_effet->isFuture()) {
            return false;
        }

        return DB::transaction(function () use ($interim, $mouvement) {
            if ($mouvement->statut === StatutMouvement::PROGRAMME) {
                $this->appliqueur->appliquer($mouvement);
            }

            $rubriqueBase = RubriquePaie::where('entreprise_id', $interim->entreprise_id)->where('code', 'SAL-BASE')->firstOrFail();
            ElementPaieSalarie::updateOrCreate(
                ['salarie_id' => $interim->salarie_id, 'rubrique_id' => $rubriqueBase->id],
                ['entreprise_id' => $interim->entreprise_id, 'montant' => $interim->reclassement_salaire_base, 'actif' => true, 'etat' => 1],
            );

            $interim->update(['reclassement_applique_le' => now()]);
            $this->audit->tracer('interim.interne.reclassement_applique', $interim, [
                'mouvement_id' => $mouvement->id,
                'nouveau_salaire_base' => $interim->reclassement_salaire_base,
            ]);

            return true;
        });
    }

    private function fermer(InterimInterne $interim, Carbon $dateFin, CauseFinInterim $cause, ?DecisionFinInterim $decision, ?string $commentaire): void
    {
        $interim->update([
            'statut' => StatutInterim::CLOS->value,
            'cause_fin' => $cause->value,
            'decision_fin' => $decision?->value,
            'date_decision' => $decision ? today() : null,
            'decide_par' => $decision ? auth()->id() : null,
            'date_fin_reelle' => $dateFin,
            'motif_cloture' => $commentaire,
        ]);

        $interim->mouvementCarriere?->update([
            'statut' => StatutMouvement::TERMINE->value,
            'date_fin_reelle' => $dateFin,
            'motif_cloture' => $cause->libelle().($commentaire ? " — {$commentaire}" : ''),
        ]);
    }

    private function statutSelonDuree(Carbon $finPrevue, ControleInterim $controle): StatutInterim
    {
        return $finPrevue->gt($controle->plafondNormal) ? StatutInterim::PROLONGE : StatutInterim::EN_COURS;
    }

    private function exigerNonClos(InterimInterne $interim): void
    {
        if ($interim->estClos()) {
            throw ValidationException::withMessages(['interim' => 'Cet intérim est clos.']);
        }
    }

    private function exigerDateFinValide(InterimInterne $interim, Carbon $date): void
    {
        if ($date->lt($interim->date_debut)) {
            throw ValidationException::withMessages([
                'date_fin_reelle' => 'La date de fin ne peut pas précéder le début de l\'intérim ('
                    .$interim->date_debut->format('d/m/Y').').',
            ]);
        }
    }

    private function salaireBase(Salarie $salarie): ?int
    {
        $montant = ElementPaieSalarie::where('salarie_id', $salarie->id)
            ->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))
            ->value('montant');

        return $montant === null ? null : (int) $montant;
    }

    /**
     * @param  array{chemin_piece:string, type_mime_piece:?string, empreinte_sha256:string}|null  $pieceEnregistree
     */
    private function historiser(
        InterimInterne $interim,
        TypeDecisionInterim $type,
        array $donnees,
        ?string $commentaire = null,
        ?UploadedFile $piece = null,
        ?array $pieceEnregistree = null,
    ): DecisionInterim {
        $pieceEnregistree ??= $piece ? $this->pieces->enregistrer($piece, "internes/{$interim->id}") : [];

        $decision = $interim->decisions()->create(array_merge([
            'entreprise_id' => $interim->entreprise_id,
            'type_decision' => $type->value,
            'donnees' => $donnees,
            'commentaire' => $commentaire,
            'decide_par' => auth()->id(),
            'decide_le' => now(),
        ], $pieceEnregistree));

        $this->audit->tracer("interim.interne.{$type->value}", $interim, $donnees);

        return $decision;
    }
}
