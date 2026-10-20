<?php

namespace App\Domain\Interim\Services;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Services\AppliqueurMouvement;
use App\Domain\Carriere\Services\GenerateurNumeroMouvement;
use App\Domain\Interim\Enums\ReponseSalarie;
use App\Domain\Interim\Enums\StatutPret;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Enums\TypePiecePret;
use App\Domain\Interim\Models\DecisionInterim;
use App\Domain\Interim\Models\PiecePretMainOeuvre;
use App\Domain\Interim\Models\PretMainOeuvre;
use App\Domain\Interim\Models\RefacturationPret;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prêt de main-d'œuvre à but non lucratif entre entreprises (Code du travail, art. 8).
 */
class PretMainOeuvreService
{
    public function __construct(
        private ComparateurCategories $comparateur,
        private GestionnairePiecesInterim $pieces,
        private GenerateurNumeroMouvement $numeroteur,
        private AppliqueurMouvement $appliqueur,
        private ServiceAudit $audit,
    ) {}

    public function creer(array $donnees): PretMainOeuvre
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;
            $salarie = Salarie::findOrFail($donnees['salarie_id']);
            $affectation = Affectation::where('salarie_id', $salarie->id)->where('en_cours', true)->latest('date_debut')->first();

            // RG-PMO-05 — situation de départ, garantie au retour.
            $pret = PretMainOeuvre::create(array_merge($donnees, [
                'entreprise_id' => $entrepriseId,
                'reference' => $this->reference($entrepriseId),
                'poste_origine_id' => $affectation?->poste_id,
                'affectation_origine_id' => $affectation?->id,
                'position_origine_id' => $this->comparateur->positionDuSalarie($salarie)?->id,
                'salaire_base_origine' => $this->salaireBase($salarie),
                'reponse_salarie' => ReponseSalarie::EN_ATTENTE->value,
                'statut' => StatutPret::PROJET->value,
                'cree_par' => auth()->id(),
            ]));

            $this->historiser($pret, TypeDecisionInterim::CREATION, [
                'utilisatrice' => $pret->utilisatrice_raison_sociale,
                'date_debut' => $pret->date_debut->toDateString(),
                'date_fin' => $pret->date_fin->toDateString(),
            ]);

            return $pret;
        });
    }

    /**
     * RG-PMO-02 — pièce obligatoire, signée avant la date de début. L'accord écrit du salarié
     * vaut acceptation (RG-PMO-03).
     */
    public function ajouterPiece(PretMainOeuvre $pret, TypePiecePret $type, string $dateSignature, UploadedFile $fichier): PiecePretMainOeuvre
    {
        $this->exigerStatut($pret, [StatutPret::PROJET]);
        $dateSignature = Carbon::parse($dateSignature)->startOfDay();

        if ($dateSignature->gt($pret->date_debut)) {
            throw ValidationException::withMessages(['date_signature' => "Pièce refusée : « {$type->libelle()} » doit être signée "
                .'avant le début du prêt, le '.$pret->date_debut->format('d/m/Y').' (Code du travail, art. 8).']);
        }

        return DB::transaction(function () use ($pret, $type, $dateSignature, $fichier) {
            $stockage = $this->pieces->enregistrer($fichier, "prets/{$pret->id}");

            $piece = $pret->pieces()->create([
                'entreprise_id' => $pret->entreprise_id,
                'type_piece' => $type->value,
                'date_signature' => $dateSignature,
                'chemin' => $stockage['chemin_piece'],
                'type_mime' => $stockage['type_mime_piece'],
                'empreinte_sha256' => $stockage['empreinte_sha256'],
                'cree_par' => auth()->id(),
            ]);

            if ($type === TypePiecePret::ACCORD_SALARIE) {
                $pret->update(['reponse_salarie' => ReponseSalarie::ACCEPTE->value, 'date_reponse_salarie' => $dateSignature]);
            }

            $this->historiser($pret, TypeDecisionInterim::PIECE, ['type_piece' => $type->value, 'date_signature' => $dateSignature->toDateString()],
                $type->libelle(), null, $stockage);

            return $piece;
        });
    }

    /**
     * RG-PMO-03 — le refus du salarié est enregistré et clôt le projet, sans aucune autre
     * conséquence : ni sanction ni mesure défavorable ne peuvent y être liées.
     */
    public function enregistrerRefus(PretMainOeuvre $pret, string $date, ?string $commentaire = null, ?UploadedFile $piece = null): PretMainOeuvre
    {
        $this->exigerStatut($pret, [StatutPret::PROJET]);

        return DB::transaction(function () use ($pret, $date, $commentaire, $piece) {
            $pret->update([
                'reponse_salarie' => ReponseSalarie::REFUSE->value,
                'date_reponse_salarie' => $date,
                'statut' => StatutPret::REFUSE_SALARIE->value,
            ]);

            $this->historiser($pret, TypeDecisionInterim::REFUS_SALARIE, ['date' => $date],
                trim('Refus du salarié : aucune sanction ni mesure défavorable ne peut y être liée (Code du travail, art. 8). '.($commentaire ?? '')),
                $piece);

            return $pret->fresh();
        });
    }

    /**
     * RG-PMO-02 / RG-PMO-04 — démarrage : trois pièces présentes et accord du salarié. Le contrat
     * n'est ni rompu ni suspendu ; le prêt est tracé comme mise à disposition dans la carrière,
     * sans changer le poste permanent.
     */
    public function demarrer(PretMainOeuvre $pret): PretMainOeuvre
    {
        $this->exigerStatut($pret, [StatutPret::PROJET]);

        $manquantes = $pret->piecesManquantes();
        if ($manquantes !== []) {
            throw ValidationException::withMessages(['pieces' => 'Prêt refusé : pièces obligatoires manquantes avant le début — '
                .implode(' ; ', array_map(fn ($t) => $t->libelle(), $manquantes)).' (Code du travail, art. 8).']);
        }

        if ($pret->reponse_salarie !== ReponseSalarie::ACCEPTE) {
            throw ValidationException::withMessages(['reponse_salarie' => 'Prêt refusé : l\'accord écrit du salarié est obligatoire (Code du travail, art. 8).']);
        }

        return DB::transaction(function () use ($pret) {
            $mouvement = MouvementCarriere::create([
                'entreprise_id' => $pret->entreprise_id,
                'numero_mouvement' => $this->numeroteur->generer($pret->entreprise_id),
                'salarie_id' => $pret->salarie_id,
                'type_mouvement' => TypeMouvement::MISE_A_DISPOSITION->value,
                'motif' => "Prêt de main-d'œuvre à but non lucratif ({$pret->reference}) auprès de {$pret->utilisatrice_raison_sociale} (Code du travail, art. 8)",
                'statut' => $pret->date_debut->lte(today()) ? StatutMouvement::EFFECTIF->value : StatutMouvement::PROGRAMME->value,
                'date_proposition' => now(),
                'date_effet' => $pret->date_debut,
                'date_fin_prevue' => $pret->date_fin,
                'poste_depart_id' => $pret->poste_origine_id,
                'lieu_affectation_cible' => $pret->lieu,
                'type_source' => 'manual',
                'type_saisie_source' => 'normal',
                'cree_par' => auth()->id(),
                'etat' => 1,
            ]);

            $pret->update(['statut' => StatutPret::EN_COURS->value, 'mouvement_carriere_id' => $mouvement->id]);
            $this->historiser($pret, TypeDecisionInterim::DEMARRAGE, ['mouvement_carriere_id' => $mouvement->id]);

            return $pret->fresh();
        });
    }

    /**
     * RG-PMO-01 — la refacturation ne peut pas dépasser salaires + charges sociales + frais
     * professionnels remboursés : alerte (non bloquante) en cas de marge.
     */
    public function enregistrerRefacturation(PretMainOeuvre $pret, array $donnees): RefacturationPret
    {
        $base = (int) $donnees['salaires'] + (int) $donnees['charges_sociales'] + (int) ($donnees['frais_professionnels'] ?? 0);
        $montant = (int) $donnees['montant_refacture'];

        $alerte = $montant > $base
            ? 'Marge de '.number_format($montant - $base, 0, ',', ' ').' FCFA : le montant refacturé ('.number_format($montant, 0, ',', ' ')
                .' FCFA) dépasse la base refacturable ('.number_format($base, 0, ',', ' ').' FCFA = salaires + charges sociales + '
                .'frais professionnels remboursés). Un prêt de main-d\'œuvre à but lucratif est illicite en dehors d\'une entreprise '
                .'de travail temporaire (Code du travail, art. 8).'
            : null;

        return DB::transaction(function () use ($pret, $donnees, $alerte, $base) {
            $refacturation = $pret->refacturations()->create(array_merge($donnees, [
                'entreprise_id' => $pret->entreprise_id,
                'frais_professionnels' => (int) ($donnees['frais_professionnels'] ?? 0),
                'alerte_marge' => $alerte,
                'cree_par' => auth()->id(),
            ]));

            $this->historiser($pret, TypeDecisionInterim::REFACTURATION, [
                'refacturation_id' => $refacturation->id,
                'base' => $base,
                'montant' => $refacturation->montant_refacture,
            ], $alerte);

            return $refacturation;
        });
    }

    /**
     * RG-PMO-05 — au retour, le salarié retrouve son poste ou un poste équivalent : toute baisse
     * de catégorie ou de salaire liée au retour est bloquée.
     */
    public function retour(PretMainOeuvre $pret, string $dateRetour, ?int $posteRetourId = null, ?string $commentaire = null): PretMainOeuvre
    {
        $this->exigerStatut($pret, [StatutPret::EN_COURS]);
        $dateRetour = Carbon::parse($dateRetour)->startOfDay();
        $salarie = $pret->salarie;
        $erreurs = [];

        if ($dateRetour->lt($pret->date_debut)) {
            $erreurs['date_retour'][] = 'La date de retour ne peut pas précéder le début du prêt.';
        }

        $posteRetour = Poste::with('categorieClassification')->find($posteRetourId ?? $pret->poste_origine_id);
        $posteOrigine = Poste::with('categorieClassification')->find($pret->poste_origine_id);
        if ($posteRetour && $posteOrigine && $posteRetour->id !== $posteOrigine->id) {
            $origine = $posteOrigine->categorieClassification;
            $cible = $posteRetour->categorieClassification;
            if (! $origine || ! $cible || $origine->referentiel_id !== $cible->referentiel_id) {
                $erreurs['poste_retour_id'][] = 'Retour refusé : l\'équivalence du poste de retour ne peut pas être vérifiée '
                    .'(catégories de classification non renseignées ou de référentiels différents) (Code du travail, art. 8).';
            } elseif ($cible->ordre < $origine->ordre) {
                $erreurs['poste_retour_id'][] = "Retour refusé : le poste « {$posteRetour->intitule} » ({$cible->libelle}) est de catégorie "
                    ."inférieure au poste d'origine ({$origine->libelle}). Le salarié retrouve son poste ou un poste équivalent (Code du travail, art. 8).";
            }
        }

        $salaireActuel = $this->salaireBase($salarie);
        if ($pret->salaire_base_origine !== null && ($salaireActuel ?? 0) < $pret->salaire_base_origine) {
            $erreurs['salaire'][] = 'Retour refusé : le salaire de base actuel ('.number_format((int) $salaireActuel, 0, ',', ' ')
                .' FCFA) est inférieur à celui d\'avant le prêt ('.number_format($pret->salaire_base_origine, 0, ',', ' ')
                .' FCFA). Aucune baisse de salaire ne peut être liée au retour (Code du travail, art. 8).';
        }

        $positionOrigine = $pret->positionOrigine;
        $positionActuelle = $this->comparateur->positionDuSalarie($salarie);
        if ($positionOrigine && $positionActuelle && $positionOrigine->referentiel_id === $positionActuelle->referentiel_id
            && $positionActuelle->categorie && $positionOrigine->categorie
            && $positionActuelle->categorie->ordre < $positionOrigine->categorie->ordre) {
            $erreurs['categorie'][] = "Retour refusé : la catégorie actuelle ({$positionActuelle->categorie->libelle}) est inférieure à celle "
                ."d'avant le prêt ({$positionOrigine->categorie->libelle}). Aucune baisse de catégorie ne peut être liée au retour (Code du travail, art. 8).";
        }

        if ($erreurs !== []) {
            throw ValidationException::withMessages($erreurs);
        }

        return DB::transaction(function () use ($pret, $dateRetour, $posteRetour, $posteOrigine, $commentaire) {
            // Retour sur un poste équivalent : nouvelle affectation à la date de retour.
            if ($posteRetour && $posteOrigine && $posteRetour->id !== $posteOrigine->id) {
                $affectation = MouvementCarriere::create([
                    'entreprise_id' => $pret->entreprise_id,
                    'numero_mouvement' => $this->numeroteur->generer($pret->entreprise_id),
                    'salarie_id' => $pret->salarie_id,
                    'type_mouvement' => TypeMouvement::AFFECTATION->value,
                    'motif' => "Retour de prêt de main-d'œuvre ({$pret->reference}) sur un poste équivalent (Code du travail, art. 8)",
                    'statut' => StatutMouvement::PROGRAMME->value,
                    'date_proposition' => now(),
                    'date_effet' => $dateRetour,
                    'poste_depart_id' => $posteOrigine->id,
                    'structure_cible_id' => $posteRetour->structure_id,
                    'poste_cible_id' => $posteRetour->id,
                    'type_source' => 'manual',
                    'type_saisie_source' => 'normal',
                    'cree_par' => auth()->id(),
                    'etat' => 1,
                ]);
                if ($affectation->peutEtreApplique()) {
                    $this->appliqueur->appliquer($affectation);
                }
            }

            $pret->update([
                'statut' => StatutPret::TERMINE->value,
                'date_retour' => $dateRetour,
                'poste_retour_id' => $posteRetour?->id,
            ]);
            $pret->mouvementCarriere?->update([
                'statut' => StatutMouvement::TERMINE->value,
                'date_fin_reelle' => $dateRetour,
                'motif_cloture' => 'Retour de prêt de main-d\'œuvre',
            ]);

            $this->historiser($pret, TypeDecisionInterim::RETOUR, [
                'date_retour' => $dateRetour->toDateString(),
                'poste_retour_id' => $posteRetour?->id,
            ], $commentaire);

            return $pret->fresh();
        });
    }

    public function annuler(PretMainOeuvre $pret, string $motif): PretMainOeuvre
    {
        $this->exigerStatut($pret, [StatutPret::PROJET]);

        $pret->update(['statut' => StatutPret::ANNULE->value, 'motif_annulation' => $motif]);
        $this->historiser($pret, TypeDecisionInterim::ANNULATION, [], $motif);

        return $pret->fresh();
    }

    // --- Interne ---

    /** @param list<StatutPret> $autorises */
    private function exigerStatut(PretMainOeuvre $pret, array $autorises): void
    {
        if (! in_array($pret->statut, $autorises, true)) {
            throw ValidationException::withMessages(['statut' => "Action impossible : le prêt est « {$pret->statut->libelle()} »."]);
        }
    }

    private function salaireBase(Salarie $salarie): ?int
    {
        $montant = ElementPaieSalarie::where('salarie_id', $salarie->id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->where('code', 'SAL-BASE'))->value('montant');

        return $montant === null ? null : (int) $montant;
    }

    private function reference(int $entrepriseId): string
    {
        $annee = now()->year;
        $rang = PretMainOeuvre::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)
            ->where('reference', 'like', "PMO-{$annee}-%")->count() + 1;

        return sprintf('PMO-%d-%05d', $annee, $rang);
    }

    /** @param array{chemin_piece:string, type_mime_piece:?string, empreinte_sha256:string}|null $pieceEnregistree */
    private function historiser(PretMainOeuvre $pret, TypeDecisionInterim $type, array $donnees, ?string $commentaire = null,
        ?UploadedFile $piece = null, ?array $pieceEnregistree = null): DecisionInterim
    {
        $pieceEnregistree ??= $piece ? $this->pieces->enregistrer($piece, "prets/{$pret->id}") : [];

        $decision = $pret->decisions()->create(array_merge([
            'entreprise_id' => $pret->entreprise_id,
            'type_decision' => $type->value,
            'donnees' => $donnees,
            'commentaire' => $commentaire,
            'decide_par' => auth()->id(),
            'decide_le' => now(),
        ], $pieceEnregistree));

        $this->audit->tracer("interim.pret.{$type->value}", $pret, $donnees);

        return $decision;
    }
}
