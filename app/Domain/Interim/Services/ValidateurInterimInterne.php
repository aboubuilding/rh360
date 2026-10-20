<?php

namespace App\Domain\Interim\Services;

use App\Domain\Classification\Models\CategorieClassification;
use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use Carbon\Carbon;

/**
 * Contrôles métier d'un intérim interne, partagés par les FormRequests et le service.
 * Chaque refus porte le code de la règle et l'article cité.
 */
class ValidateurInterimInterne
{
    public function __construct(
        private ComparateurCategories $comparateur,
        private CalculateurDelaisInterim $delais,
        private ParametresInterim $parametres,
    ) {}

    /**
     * @param  array{salarie_id:int, poste_occupe_id:int, titulaire_id?:?int, absence_titulaire_id?:?int,
     *               motif:string, date_debut:string, date_fin_prevue:string, date_fin_motif?:?string}  $donnees
     */
    public function controler(array $donnees, ?int $interimExistantId = null): ControleInterim
    {
        $controle = new ControleInterim;

        $salarie = Salarie::find($donnees['salarie_id']);
        $poste = Poste::find($donnees['poste_occupe_id']);
        $motif = MotifInterim::from($donnees['motif']);
        $debut = Carbon::parse($donnees['date_debut'])->startOfDay();
        $finPrevue = Carbon::parse($donnees['date_fin_prevue'])->startOfDay();
        $dateFinMotif = ! empty($donnees['date_fin_motif']) ? Carbon::parse($donnees['date_fin_motif']) : null;
        $titulaireId = $donnees['titulaire_id'] ?? null;
        $absence = ! empty($donnees['absence_titulaire_id']) ? Absence::find($donnees['absence_titulaire_id']) : null;

        if ($titulaireId && (int) $titulaireId === $salarie->id) {
            $controle->erreur('titulaire_id', 'Le titulaire remplacé ne peut pas être le salarié en intérim.');
        }

        if ($absence && (int) $absence->salarie_id !== (int) $titulaireId) {
            $controle->erreur('absence_titulaire_id', 'L\'absence choisie n\'est pas celle du titulaire remplacé.');
        }

        $this->controlerCategorie($controle, $salarie, $poste);
        $this->controlerDuree($controle, $motif, $poste, $titulaireId, $debut, $finPrevue, $dateFinMotif, $absence, $interimExistantId);

        return $controle;
    }

    // RG-INT-01 — La catégorie du poste occupé doit être strictement supérieure à celle du salarié.
    private function controlerCategorie(ControleInterim $controle, Salarie $salarie, Poste $poste): void
    {
        $occupee = $poste->categorie_classification_id
            ? CategorieClassification::find($poste->categorie_classification_id)
            : null;

        if (! $occupee) {
            $controle->erreur('poste_occupe_id',
                "Intérim refusé : le poste « {$poste->intitule} » n'est rattaché à aucune catégorie de classification, "
                .'la règle de la catégorie supérieure ne peut pas être vérifiée.');

            return;
        }

        $origine = $this->comparateur->categorieDuSalarie($salarie, $occupee);
        if (! $origine) {
            $controle->erreur('salarie_id',
                'Intérim refusé : la catégorie du salarié est introuvable dans le référentiel du poste occupé, '
                .'la règle de la catégorie supérieure ne peut pas être vérifiée.');

            return;
        }

        $controle->categorieOccupee = $occupee;
        $controle->categorieOrigine = $origine;

        if (! $this->comparateur->estStrictementSuperieure($occupee, $origine)) {
            $controle->erreur('poste_occupe_id',
                "Intérim refusé : la catégorie du poste occupé ({$occupee->libelle}) doit être strictement supérieure "
                ."à celle du salarié ({$origine->libelle}).");
        }
    }

    // RG-INT-03, RG-INT-04, RG-INT-05 — Plafond de deux mois, sauf motif éligible et dans la limite du délai légal.
    private function controlerDuree(
        ControleInterim $controle,
        MotifInterim $motif,
        Poste $poste,
        ?int $titulaireId,
        Carbon $debut,
        Carbon $finPrevue,
        ?Carbon $dateFinMotif,
        ?Absence $absence,
        ?int $interimExistantId,
    ): void {
        if ($finPrevue->lt($debut)) {
            $controle->erreur('date_fin_prevue', 'La date de fin prévue doit être postérieure à la date de début.');

            return;
        }

        $dejaEffectues = $this->delais->joursDejaEffectues($poste->id, $titulaireId, $debut, $interimExistantId);
        $plafondNormal = $this->delais->plafondNormal($debut, $dejaEffectues);
        $controle->plafondNormal = $plafondNormal;
        $controle->joursDejaEffectues = $dejaEffectues;

        $mentionCumul = $dejaEffectues > 0
            ? " ({$dejaEffectues} jours déjà effectués sur ce poste pour ce titulaire)"
            : '';

        // RG-INT-04 — Motif non éligible : plafond de deux mois.
        if (! $motif->estEligibleProlongation()) {
            $controle->dateLimite = $plafondNormal;
            if ($finPrevue->gt($plafondNormal)) {
                $controle->erreur('date_fin_prevue',
                    'Intérim refusé : la durée maximale est de '.$this->parametres->get('duree_max_interim_mois')
                    ." mois{$mentionCumul}, soit une fin au plus tard le {$plafondNormal->format('d/m/Y')}. "
                    ."Le motif « {$motif->libelle()} » ne permet pas de prolongation.");
            }

            return;
        }

        // RG-INT-05 — Motif éligible : plafond du délai légal du motif.
        $plafondLegal = $this->delais->plafondLegal($motif, $debut, $dateFinMotif, $absence, $dejaEffectues);
        $delai = $this->delais->delaiLegal($motif);

        if ($plafondLegal === null && $delai === 'fin_conge') {
            $controle->erreur('date_fin_motif',
                'La date de fin du congé du titulaire est obligatoire pour fixer le plafond de l\'intérim.');

            return;
        }

        // Consolidation ou guérison non encore connue : la fin prévue sert de date limite
        // jusqu'à ce que la date soit saisie.
        if ($plafondLegal === null) {
            $controle->dateLimite = $finPrevue->max($plafondNormal);

            return;
        }

        $controle->dateLimite = $plafondLegal->max($plafondNormal);

        if ($finPrevue->gt($controle->dateLimite)) {
            $controle->erreur('date_fin_prevue',
                "Intérim refusé : la fin prévue dépasse le délai légal du motif « {$motif->libelle()} », "
                ."soit au plus tard le {$controle->dateLimite->format('d/m/Y')}.");
        }
    }
}
