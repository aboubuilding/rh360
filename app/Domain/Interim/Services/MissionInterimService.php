<?php

namespace App\Domain\Interim\Services;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Conges\Models\Absence;
use App\Domain\Interim\Enums\StatutMission;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Enums\TypeMotifMission;
use App\Domain\Interim\Models\DecisionInterim;
use App\Domain\Interim\Models\EntrepriseInterim;
use App\Domain\Interim\Models\FactureInterim;
use App\Domain\Interim\Models\MissionInterim;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Affectation;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Travail intérimaire par agence (Code du travail, art. 60 à 63, 356). L'intérimaire reste
 * salarié de l'agence : la mission n'entre ni dans l'effectif, ni dans la paie (RG-TT-03, RG-TT-04).
 */
class MissionInterimService
{
    // RG-TT-10 — sanction encourue en cas de remplacement de gréviste ou de travaux dangereux.
    public const SANCTION = 'Sanction encourue (Code du travail, art. 356) : amende de 500 000 à 2 000 000 FCFA ; '
        .'en récidive, amende de 1 000 000 à 5 000 000 FCFA et/ou emprisonnement de 1 à 6 mois.';

    public function __construct(
        private ParametresInterim $parametres,
        private CalculateurDelaisInterim $delais,
        private RepertoireBareme $bareme,
        private GestionnairePiecesInterim $pieces,
        private ServiceAudit $audit,
    ) {}

    /**
     * Contrôles bloquants (RG-TT-01, 05, 06, durée) et alerte de prudence sur le salaire (RG-TT-09).
     *
     * @return array{erreurs: array<string, list<string>>, alerte_salaire: ?string, minimum: ?int}
     */
    public function controler(array $donnees, int $renouvellements = 0): array
    {
        $erreurs = [];
        $debut = Carbon::parse($donnees['date_debut'])->startOfDay();
        $fin = Carbon::parse($donnees['date_fin'])->startOfDay();
        $agence = EntrepriseInterim::find($donnees['entreprise_interim_id']);
        $poste = Poste::with('categorieClassification')->find($donnees['poste_id']);
        $typeMotif = TypeMotifMission::from($donnees['type_motif']);

        if ($fin->lt($debut)) {
            $erreurs['date_fin'][] = 'La date de fin doit être postérieure à la date de début.';
        }

        // RG-TT-01 — agence agréée par le ministre chargé du travail.
        if (! $agence || ! $agence->agrementValideSur($debut, $fin)) {
            $erreurs['entreprise_interim_id'][] = 'Mission refusée : l\'entreprise de travail temporaire « '
                .($agence?->raison_sociale ?? '?').' » n\'a pas d\'agrément valide du ministre chargé du travail '
                .'sur toute la durée de la mission (Code du travail, art. 60 à 63).';
        }

        // RG-TT-05 — remplacement de gréviste interdit.
        if ($typeMotif === TypeMotifMission::REMPLACEMENT_GREVISTE) {
            $erreurs['type_motif'][] = 'Mission refusée : remplacement de gréviste interdit (Code du travail, art. 62). '.self::SANCTION;
        } elseif ($greviste = $this->titulaireEnGreve($poste, $donnees['titulaire_remplace_id'] ?? null, $debut, $fin)) {
            $erreurs['poste_id'][] = "Mission refusée : remplacement de gréviste interdit — {$greviste} est en grève sur la période "
                .'(Code du travail, art. 62). '.self::SANCTION;
        }

        // RG-TT-06 — travaux particulièrement dangereux interdits.
        if ($poste?->travaux_dangereux) {
            $erreurs['poste_id'][] = "Mission refusée : le poste « {$poste->intitule} » comporte des travaux particulièrement "
                .'dangereux, interdits au travail intérimaire (Code du travail, art. 60 à 63). '.self::SANCTION;
        }

        // Décret n°2022-027/PR (Code art. 63) : limites appliquées seulement si elles sont paramétrées.
        $dureeMax = $this->parametres->get('missions_duree_max_mois');
        if ($dureeMax && $fin->gt($this->delais->finDuree($debut, (int) $dureeMax))) {
            $erreurs['date_fin'][] = "Mission refusée : la durée dépasse {$dureeMax} mois (paramètre de l'entreprise, décret n°2022-027/PR).";
        }
        $renouvellementsMax = $this->parametres->get('missions_renouvellements_max');
        if ($renouvellementsMax !== null && $renouvellements > (int) $renouvellementsMax) {
            $erreurs['date_fin'][] = "Renouvellement refusé : {$renouvellementsMax} renouvellement(s) au plus "
                .'(paramètre de l\'entreprise, décret n°2022-027/PR).';
        }

        [$alerte, $minimum] = $this->controlerSalaire($poste, $donnees['salaire_convenu'] ?? null, $debut);

        return ['erreurs' => $erreurs, 'alerte_salaire' => $alerte, 'minimum' => $minimum];
    }

    public function creer(array $donnees): MissionInterim
    {
        $controle = $this->controler($donnees);
        if ($controle['erreurs'] !== []) {
            throw ValidationException::withMessages($controle['erreurs']);
        }

        return DB::transaction(function () use ($donnees, $controle) {
            $entrepriseId = auth()->user()->entreprise_id;

            $mission = MissionInterim::create(array_merge($donnees, [
                'entreprise_id' => $entrepriseId,
                'reference' => $this->reference($entrepriseId),
                'salaire_minimum_reference' => $controle['minimum'],
                'alerte_salaire' => $controle['alerte_salaire'],
                'statut' => StatutMission::PLANIFIEE->value,
                'cree_par' => auth()->id(),
            ]));

            $this->historiser($mission, TypeDecisionInterim::CREATION, [
                'agence' => $mission->entreprise_interim_id,
                'date_debut' => $mission->date_debut->toDateString(),
                'date_fin' => $mission->date_fin->toDateString(),
                'alerte_salaire' => $controle['alerte_salaire'],
            ]);

            return $mission;
        });
    }

    /**
     * RG-TT-07 — Checklist SST : accueil sécurité, EPI remis, consignes du poste.
     */
    public function validerChecklist(MissionInterim $mission, array $dates, ?UploadedFile $piece = null): MissionInterim
    {
        $this->exigerNonFinale($mission);

        return DB::transaction(function () use ($mission, $dates, $piece) {
            $mission->update([
                'sst_accueil_le' => $dates['sst_accueil_le'],
                'sst_epi_remis_le' => $dates['sst_epi_remis_le'],
                'sst_consignes_le' => $dates['sst_consignes_le'],
                'sst_valide_par' => auth()->id(),
                'sst_valide_le' => now(),
            ]);

            $tardif = collect($dates)->contains(fn ($d) => Carbon::parse($d)->gt($mission->date_debut));
            $this->historiser($mission, TypeDecisionInterim::CHECKLIST_SST, $dates + ['apres_premier_jour' => $tardif],
                $tardif ? 'Checklist validée après le premier jour de mission.' : null, $piece);

            return $mission->fresh();
        });
    }

    /**
     * RG-TT-07 — La mission ne démarre qu'une fois la checklist SST validée ; l'agrément est revérifié.
     */
    public function demarrer(MissionInterim $mission): MissionInterim
    {
        if ($mission->statut !== StatutMission::PLANIFIEE) {
            throw ValidationException::withMessages(['statut' => 'Seule une mission planifiée peut démarrer.']);
        }

        if (! $mission->checklistSstComplete()) {
            throw ValidationException::withMessages(['checklist' => 'Mission non démarrée : la checklist SST (accueil sécurité, '
                .'EPI remis, consignes du poste) doit être validée avant le premier jour ; l\'entreprise utilisatrice répond '
                .'de la santé, de la sécurité et de l\'hygiène de l\'intérimaire (Code du travail, art. 60 à 63).']);
        }

        if (! $mission->agence?->agrementValideSur($mission->date_debut, $mission->date_fin)) {
            throw ValidationException::withMessages(['entreprise_interim_id' => 'Mission non démarrée : l\'agrément de l\'agence '
                .'n\'est plus valide (Code du travail, art. 60 à 63).']);
        }

        $mission->update(['statut' => StatutMission::EN_COURS->value]);
        $this->historiser($mission, TypeDecisionInterim::DEMARRAGE, []);

        return $mission->fresh();
    }

    public function renouveler(MissionInterim $mission, string $nouvelleFin, ?string $commentaire = null, ?UploadedFile $piece = null): MissionInterim
    {
        $this->exigerNonFinale($mission);
        $nouvelleFin = Carbon::parse($nouvelleFin)->startOfDay();

        if ($nouvelleFin->lte($mission->date_fin)) {
            throw ValidationException::withMessages(['date_fin' => 'La nouvelle date de fin doit être postérieure à la fin actuelle.']);
        }

        $controle = $this->controler([
            'entreprise_interim_id' => $mission->entreprise_interim_id,
            'poste_id' => $mission->poste_id,
            'type_motif' => $mission->type_motif->value,
            'titulaire_remplace_id' => $mission->titulaire_remplace_id,
            'date_debut' => $mission->date_debut->toDateString(),
            'date_fin' => $nouvelleFin->toDateString(),
            'salaire_convenu' => $mission->salaire_convenu,
        ], $mission->nombre_renouvellements + 1);

        if ($controle['erreurs'] !== []) {
            throw ValidationException::withMessages($controle['erreurs']);
        }

        return DB::transaction(function () use ($mission, $nouvelleFin, $controle, $commentaire, $piece) {
            $ancienne = $mission->date_fin->toDateString();
            $mission->update([
                'date_fin' => $nouvelleFin,
                'nombre_renouvellements' => $mission->nombre_renouvellements + 1,
                'alerte_salaire' => $controle['alerte_salaire'],
                'salaire_minimum_reference' => $controle['minimum'],
            ]);
            $this->historiser($mission, TypeDecisionInterim::RENOUVELLEMENT,
                ['ancienne_fin' => $ancienne, 'nouvelle_fin' => $nouvelleFin->toDateString()], $commentaire, $piece);

            return $mission->fresh();
        });
    }

    public function terminer(MissionInterim $mission, string $dateFin, ?string $commentaire = null): MissionInterim
    {
        $this->exigerNonFinale($mission);
        $dateFin = Carbon::parse($dateFin)->startOfDay();

        if ($dateFin->lt($mission->date_debut)) {
            throw ValidationException::withMessages(['date_fin_reelle' => 'La date de fin ne peut pas précéder le début de la mission.']);
        }

        $mission->update(['statut' => StatutMission::TERMINEE->value, 'date_fin_reelle' => $dateFin]);
        $this->historiser($mission, TypeDecisionInterim::CLOTURE, ['date_fin_reelle' => $dateFin->toDateString()], $commentaire);

        return $mission->fresh();
    }

    public function annuler(MissionInterim $mission, string $motif): MissionInterim
    {
        $this->exigerNonFinale($mission);

        $mission->update(['statut' => StatutMission::ANNULEE->value, 'motif_annulation' => $motif]);
        $this->historiser($mission, TypeDecisionInterim::ANNULATION, [], $motif);

        return $mission->fresh();
    }

    // RG-TT-04 — seule la facture de l'agence est enregistrée.
    public function enregistrerFacture(EntrepriseInterim $agence, array $donnees, ?UploadedFile $piece = null): FactureInterim
    {
        $chemin = $piece ? $this->pieces->enregistrer($piece, "agences/{$agence->id}/factures")['chemin_piece'] : null;

        $facture = $agence->factures()->create(array_merge($donnees, [
            'entreprise_id' => $agence->entreprise_id,
            'chemin_piece' => $chemin,
            'cree_par' => auth()->id(),
        ]));

        $this->audit->tracer('interim.facture.enregistree', $facture, ['numero' => $facture->numero_facture, 'montant' => $facture->montant]);

        return $facture;
    }

    // --- Interne ---

    /**
     * RG-TT-05 — Le titulaire remplacé, ou un titulaire du poste, en absence de type grève sur la période.
     */
    private function titulaireEnGreve(?Poste $poste, ?int $titulaireId, Carbon $debut, Carbon $fin): ?string
    {
        $titulaires = $poste
            ? Affectation::where('poste_id', $poste->id)->where('en_cours', true)->pluck('salarie_id')->all()
            : [];
        if ($titulaireId) {
            $titulaires[] = (int) $titulaireId;
        }
        if ($titulaires === []) {
            return null;
        }

        $absence = Absence::query()
            ->whereIn('salarie_id', array_unique($titulaires))
            ->whereHas('typeConge', fn ($q) => $q->whereIn('code', (array) $this->parametres->get('codes_absences_greve')))
            ->where('debut_le', '<=', $fin->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('fin_le')->orWhere('fin_le', '>=', $debut))
            ->with('salarie')
            ->first();

        return $absence?->salarie?->nom_complet;
    }

    /**
     * RG-TT-09 — Alerte (non bloquante) si le salaire convenu avec l'agence est inférieur au minimum
     * CCIT de la catégorie du poste ; l'obligation pèse sur l'agence.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function controlerSalaire(?Poste $poste, mixed $salaire, Carbon $date): array
    {
        if ($salaire === null || $salaire === '') {
            return ['Salaire convenu avec l\'agence non renseigné : contrôle de prudence impossible (Code du travail, art. 155 ; CCIT, art. 28).', null];
        }

        if (! $poste?->categorieClassification) {
            return ['Poste sans catégorie de classification : contrôle de prudence du salaire impossible (Code du travail, art. 155 ; CCIT, art. 28).', null];
        }

        try {
            $minimum = $this->bareme->salaireMinimum($poste->categorieClassification, $date);
        } catch (\DomainException $e) {
            return [$e->getMessage().' Contrôle de prudence du salaire impossible.', null];
        }

        if ($minimum === null) {
            return ['Barème introuvable pour la catégorie du poste : contrôle de prudence du salaire impossible (CCIT, art. 28).', null];
        }

        if ((int) $salaire < $minimum) {
            return ['Salaire convenu ('.number_format((int) $salaire, 0, ',', ' ').' FCFA) inférieur au minimum de la catégorie '
                .$poste->categorieClassification->code.' ('.number_format($minimum, 0, ',', ' ').' FCFA) : à signaler à l\'agence, '
                .'à qui incombe l\'obligation (Code du travail, art. 155 ; CCIT, art. 28).', $minimum];
        }

        return [null, $minimum];
    }

    private function reference(int $entrepriseId): string
    {
        $annee = now()->year;
        $rang = MissionInterim::withoutGlobalScopes()->where('entreprise_id', $entrepriseId)
            ->where('reference', 'like', "MIS-{$annee}-%")->count() + 1;

        return sprintf('MIS-%d-%05d', $annee, $rang);
    }

    private function exigerNonFinale(MissionInterim $mission): void
    {
        if ($mission->statut->estFinal()) {
            throw ValidationException::withMessages(['statut' => 'Cette mission est '.mb_strtolower($mission->statut->libelle()).'.']);
        }
    }

    private function historiser(MissionInterim $mission, TypeDecisionInterim $type, array $donnees, ?string $commentaire = null, ?UploadedFile $piece = null): DecisionInterim
    {
        $pieceEnregistree = $piece ? $this->pieces->enregistrer($piece, "missions/{$mission->id}") : [];

        $decision = $mission->decisions()->create(array_merge([
            'entreprise_id' => $mission->entreprise_id,
            'type_decision' => $type->value,
            'donnees' => $donnees,
            'commentaire' => $commentaire,
            'decide_par' => auth()->id(),
            'decide_le' => now(),
        ], $pieceEnregistree));

        $this->audit->tracer("interim.mission.{$type->value}", $mission, $donnees);

        return $decision;
    }
}
