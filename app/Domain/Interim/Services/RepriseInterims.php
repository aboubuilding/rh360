<?php

namespace App\Domain\Interim\Services;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Enums\TypeMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Enums\StatutInterim;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Interim\Models\InterimInterne;
use App\Domain\Paie\Models\ElementPaieSalarie;
use App\Domain\Personnel\Models\Affectation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reprise des intérims saisis avant la refonte (mouvements de carrière de type « interim »)
 * dans interims_internes. Idempotente, ne supprime rien ; les cas ambigus restent au rapport.
 */
class RepriseInterims
{
    // Mots-clés du motif libre → motif typé. Un motif qui n'en contient aucun, ou plusieurs
    // motifs différents, est ambigu et n'est pas repris.
    private const MOTS_CLES = [
        'maladie professionnelle' => MotifInterim::AT_MP,
        'accident du travail' => MotifInterim::AT_MP,
        'maternit' => MotifInterim::CONGE_MATERNITE,
        'congé annuel' => MotifInterim::CONGE_ANNUEL,
        'conge annuel' => MotifInterim::CONGE_ANNUEL,
        'détention' => MotifInterim::DETENTION_PREVENTIVE,
        'detention' => MotifInterim::DETENTION_PREVENTIVE,
        'maladie' => MotifInterim::MALADIE_ACCIDENT,
        'accident' => MotifInterim::MALADIE_ACCIDENT,
        'vacant' => MotifInterim::POSTE_VACANT,
        'réorganisation' => MotifInterim::REORGANISATION,
        'reorganisation' => MotifInterim::REORGANISATION,
    ];

    // Seuls les actes validés sont repris ; les propositions en cours restent dans le circuit.
    private const STATUTS_REPRIS = [StatutMouvement::VALIDE, StatutMouvement::PROGRAMME, StatutMouvement::EFFECTIF, StatutMouvement::TERMINE];

    public function __construct(
        private ValidateurInterimInterne $validateur,
        private ComparateurCategories $comparateur,
        private ParametresInterim $parametres,
        private InterimInterneService $service,
    ) {}

    public function executer(bool $simulation, ?int $entrepriseId = null): RapportReprise
    {
        $rapport = new RapportReprise($simulation, 'REPRISE-INTERIMS-'.now()->format('YmdHis'));

        $mouvements = MouvementCarriere::withoutGlobalScopes()
            ->whereIn('type_mouvement', [TypeMouvement::INTERIM->value, TypeMouvement::DETACHEMENT->value, TypeMouvement::MISE_A_DISPOSITION->value])
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->orderBy('id')
            ->get();

        foreach ($mouvements as $mouvement) {
            $this->parametres->pourEntreprise($mouvement->entreprise_id);
            $this->reprendre($mouvement, $rapport);
        }

        // Travail intérimaire d'agence enregistré comme contrat salarié : contraire à RG-TT-03 / RG-TT-04.
        Contrat::withoutGlobalScopes()
            ->where('type_contrat', TypeContrat::INTERIM->value)
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->with('salarie')
            ->each(fn (Contrat $c) => $rapport->ligne(
                'contrat', $c->id, $c->reference, $c->salarie?->nom_complet,
                'Contrat '.$c->getRawOriginal('type_contrat').' ('.$c->getRawOriginal('statut').') du '.$c->date_debut?->format('d/m/Y'),
                'Non repris',
                'Intérimaire d\'agence enregistré comme salarié : il est compté dans l\'effectif et passe en paie '
                .'(Code du travail, art. 60 à 63). À reprendre en mission d\'intérim (module B).',
            ));

        return $rapport;
    }

    private function reprendre(MouvementCarriere $m, RapportReprise $rapport): void
    {
        $ancien = $this->decrire($m);
        $ligne = fn (string $nouvel, ?string $anomalie = null) => $rapport->ligne(
            'mouvement_carriere', $m->id, $m->numero_mouvement, $m->salarie()->withoutGlobalScopes()->first()?->nom_complet,
            $ancien, $nouvel, $anomalie,
        );

        if ($m->type_mouvement !== TypeMouvement::INTERIM) {
            $ligne('Non repris', "{$m->type_mouvement->libelle()} : à qualifier par les RH (prêt de main-d'œuvre — "
                .'module C — ou autre acte), non couvert par la reprise des intérims internes.');

            return;
        }

        $existant = InterimInterne::withoutGlobalScopes()->where('mouvement_carriere_id', $m->id)->first();
        if ($existant) {
            $ligne("Déjà repris : intérim interne #{$existant->id} ({$existant->statut->libelle()})");

            return;
        }

        if (! in_array($m->statut, self::STATUTS_REPRIS, true)) {
            $ligne('Non repris', "Mouvement au statut « {$m->statut->libelle()} » : non validé, à ressaisir dans le nouveau module si besoin.");

            return;
        }

        $fin = $m->date_fin_reelle ?? $m->date_fin_prevue;
        if (! $m->poste_cible_id || ! $m->date_effet || ! $fin) {
            $ligne('Non repris', 'Données incomplètes : poste occupé, date de début ou date de fin manquant.');

            return;
        }

        $motif = $this->qualifierMotif($m->motif);
        if (! $motif) {
            $ligne('Non repris', 'Motif « '.($m->motif ?: 'non renseigné').' » ambigu : à qualifier par les RH '
                .'(maladie, congé annuel ou de maternité, détention préventive, AT/MP, poste vacant, réorganisation, autre).');

            return;
        }

        $controle = $this->validateur->controler([
            'salarie_id' => $m->salarie_id,
            'poste_occupe_id' => $m->poste_cible_id,
            'titulaire_id' => null,
            'motif' => $motif->value,
            'date_debut' => $m->date_effet->toDateString(),
            'date_fin_prevue' => $m->date_fin_prevue?->toDateString() ?? $fin->toDateString(),
        ]);

        // Catégorie, titulaire ou date de fin du motif : ambiguïté qui empêche la reprise.
        $bloquantes = array_diff_key($controle->erreurs, ['date_fin_prevue' => true]);
        if ($bloquantes !== []) {
            $ligne('Non repris', implode(' ', array_merge(...array_values($bloquantes))));

            return;
        }

        $dureeHorsRegle = $controle->erreurs['date_fin_prevue'][0] ?? null;
        $termine = $m->statut === StatutMouvement::TERMINE;
        $delaiExpire = ! $termine && today()->gt($controle->dateLimite);

        $statut = match (true) {
            $termine => StatutInterim::CLOS,
            $dureeHorsRegle !== null || $delaiExpire => StatutInterim::A_REGULARISER,
            $m->date_fin_prevue && $m->date_fin_prevue->gt($controle->plafondNormal) => StatutInterim::PROLONGE,
            default => StatutInterim::EN_COURS,
        };

        $anomalies = array_filter([
            $dureeHorsRegle,
            $delaiExpire ? "Délai expiré le {$controle->dateLimite->format('d/m/Y')} sans décision de reclassement ou de retour (CCIT, art. 13)." : null,
            'Titulaire remplacé non renseigné dans l\'ancien acte.',
        ]);

        $nouvel = "interims_internes, statut « {$statut->libelle()} », motif « {$motif->libelle()} », "
            ."date limite {$controle->dateLimite->format('d/m/Y')}";

        if ($rapport->simulation) {
            $ligne('[simulation] '.$nouvel, implode(' ', $anomalies) ?: null);

            return;
        }

        $interim = DB::transaction(fn () => $this->creer($m, $motif, $controle, $statut, $rapport->lot));
        $ligne("Intérim interne #{$interim->id} : ".$nouvel, implode(' ', $anomalies) ?: null);
    }

    private function creer(MouvementCarriere $m, MotifInterim $motif, ControleInterim $controle, StatutInterim $statut, string $lot): InterimInterne
    {
        $affectation = Affectation::withoutGlobalScopes()->where('salarie_id', $m->salarie_id)->where('en_cours', true)->latest('date_debut')->first();
        $salaire = ElementPaieSalarie::withoutGlobalScopes()->where('salarie_id', $m->salarie_id)->where('actif', true)
            ->whereHas('rubrique', fn ($q) => $q->withoutGlobalScopes()->where('code', 'SAL-BASE'))->value('montant');

        $interim = InterimInterne::create([
            'entreprise_id' => $m->entreprise_id,
            'mouvement_carriere_id' => $m->id,
            'salarie_id' => $m->salarie_id,
            'poste_occupe_id' => $m->poste_cible_id,
            'categorie_occupee_id' => $controle->categorieOccupee->id,
            'categorie_origine_id' => $controle->categorieOrigine->id,
            'motif' => $motif->value,
            'precision_motif' => $m->motif,
            'date_debut' => $m->date_effet,
            'date_fin_prevue' => $m->date_fin_prevue ?? $m->date_fin_reelle,
            'date_limite' => $controle->dateLimite,
            'date_fin_reelle' => $statut === StatutInterim::CLOS ? ($m->date_fin_reelle ?? $m->date_fin_prevue) : null,
            'motif_cloture' => $statut === StatutInterim::CLOS ? $m->motif_cloture : null,
            'salaire_base_origine' => $salaire === null ? null : (int) $salaire,
            'position_origine_id' => $m->position_classification_depart_id,
            'affectation_origine_id' => $affectation?->id,
            'statut' => $statut === StatutInterim::A_REGULARISER ? StatutInterim::EN_COURS->value : $statut->value,
            'reference_lot_reprise' => $lot,
            'cree_par' => auth()->id(),
        ]);

        $interim->decisions()->create([
            'entreprise_id' => $m->entreprise_id,
            'type_decision' => TypeDecisionInterim::REPRISE->value,
            'donnees' => ['mouvement_carriere_id' => $m->id, 'lot' => $lot, 'motif_libre' => $m->motif],
            'commentaire' => "Reprise de l'intérim {$m->numero_mouvement} saisi avant la mise en conformité.",
            'decide_par' => auth()->id(),
            'decide_le' => now(),
        ]);

        // Jamais clôturé automatiquement : un intérim en cours hors règle passe à régulariser.
        if ($statut === StatutInterim::A_REGULARISER) {
            $this->service->passerARegulariser($interim, 'Intérim repris hors des règles de durée (CCIT, art. 13).');
        }

        return $interim;
    }

    private function qualifierMotif(?string $libre): ?MotifInterim
    {
        $texte = Str::lower((string) $libre);
        $trouves = [];

        foreach (self::MOTS_CLES as $mot => $motif) {
            if (str_contains($texte, $mot)) {
                $trouves[$motif->value] = $motif;
                // « maladie professionnelle » ne doit pas aussi compter comme « maladie ».
                $texte = str_replace($mot, '', $texte);
            }
        }

        return count($trouves) === 1 ? array_values($trouves)[0] : null;
    }

    private function decrire(MouvementCarriere $m): string
    {
        return "{$m->type_mouvement->libelle()} {$m->statut->libelle()}, du ".($m->date_effet?->format('d/m/Y') ?? '?')
            .' au '.(($m->date_fin_reelle ?? $m->date_fin_prevue)?->format('d/m/Y') ?? '?')
            .', poste #'.($m->poste_cible_id ?? '?').', motif « '.($m->motif ?? '').' »';
    }
}
