<?php

namespace App\Domain\Conges\Services;

use App\Domain\Conges\Enums\BasePlafond;
use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\FamilleDemande;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\PeriodePlafond;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use App\Domain\Conges\Models\EvenementPermission;
use App\Domain\Conges\Models\TypeConge;
use Illuminate\Support\Facades\DB;

/**
 * Catalogue des types de demande et des événements de permission tirés du Code du travail
 * (loi n°2021-012) et de la CCIT 2026 — idempotent.
 *
 * Un type absent est créé. Un type existant reçoit les règles du catalogue (famille, délais,
 * plafond…) tant qu'elles n'ont pas été renseignées ; ses autres réglages (rémunération,
 * impacts, justificatif…) ne sont remplacés qu'avec l'option « aligner ». Un événement absent
 * est créé ; un événement existant n'est modifié qu'avec « aligner ».
 */
class InstallateurCatalogueDemandes
{
    // Colonnes ajoutées par le socle des demandes : renseignées à la première installation.
    private const REGLES = [
        'famille', 'de_droit', 'delai_depot', 'delai_depot_unite', 'delai_information_heures',
        'delai_justification_depart', 'plafond_valeur', 'plafond_unite', 'plafond_periode',
        'plafond_base', 'anciennete_min_mois', 'prise_continue_autorisee', 'report_credit_autorise',
    ];

    /**
     * @return array{crees: int, completes: int, alignes: int, evenements: int, a_verifier: array<int, string>}
     */
    public function installer(int $entrepriseId, bool $aligner = false): array
    {
        return DB::transaction(function () use ($entrepriseId, $aligner) {
            $resultat = ['crees' => 0, 'completes' => 0, 'alignes' => 0, 'evenements' => 0, 'a_verifier' => []];

            foreach ($this->types() as $code => $valeurs) {
                $type = TypeConge::pourEntreprise($entrepriseId)->where('code', $code)->first();

                if (! $type) {
                    TypeConge::create($valeurs + ['entreprise_id' => $entrepriseId, 'code' => $code, 'actif' => true, 'etat' => 1]);
                    $resultat['crees']++;

                    continue;
                }

                if ($aligner) {
                    $type->update($valeurs);
                    $resultat['alignes']++;
                } elseif ($type->famille === null) {
                    $type->update(array_intersect_key($valeurs, array_flip(self::REGLES)));
                    $resultat['completes']++;
                }
            }

            $permission = TypeConge::pourEntreprise($entrepriseId)->where('code', 'PERM-EVT')->sole();
            foreach ($this->evenements() as $ordre => [$code, $libelle, $lien, $jours, $delaiPriseMois, $piece]) {
                $valeurs = [
                    'type_conge_id' => $permission->id, 'libelle' => $libelle, 'lien_famille' => $lien,
                    'jours' => $jours, 'delai_prise_mois' => $delaiPriseMois, 'piece_attendue' => $piece,
                    'ordre' => $ordre + 1,
                ];
                $evenement = EvenementPermission::pourEntreprise($entrepriseId)->where('code', $code)->first();

                if (! $evenement) {
                    EvenementPermission::create($valeurs + ['entreprise_id' => $entrepriseId, 'code' => $code, 'actif' => true, 'etat' => 1]);
                    $resultat['evenements']++;
                } elseif ($aligner) {
                    $evenement->update($valeurs);
                }
            }

            // Types de l'entreprise que le catalogue ne connaît pas : règles à renseigner par les RH.
            $resultat['a_verifier'] = TypeConge::pourEntreprise($entrepriseId)->whereNull('famille')
                ->orderBy('code')->pluck('code')->all();

            return $resultat;
        });
    }

    /**
     * Types de demande. Les références des textes sont en commentaire et ne sont pas affichées.
     *
     * @return array<string, array<string, mixed>>
     */
    public function types(): array
    {
        $type = fn (array $valeurs) => $valeurs + [
            'categorie' => CategorieTypeConge::PERMISSION->value,
            'unite' => UniteConge::JOUR_CALENDAIRE->value,
            'droit_annuel' => 0,
            'remunere' => true,
            'justificatif_requis' => true,
            'traitement_salarial' => TraitementSalarial::MAINTIEN->value,
            'impact_conge_annuel' => ImpactCongeAnnuel::AUCUNE->value,
            'impact_anciennete' => ImpactAnciennete::MAINTENUE->value,
            'autorisation_prealable_requise' => false,
            'de_droit' => true,
            'delai_depot' => null,
            'delai_depot_unite' => null,
            'delai_information_heures' => null,
            'delai_justification_jours' => null,
            'delai_justification_depart' => null,
            'duree_max' => null,
            'portee_duree_max' => null,
            'plafond_valeur' => null,
            'plafond_unite' => null,
            'plafond_periode' => null,
            'plafond_base' => null,
            'anciennete_min_mois' => null,
            'prise_continue_autorisee' => true,
            'report_credit_autorise' => true,
        ];

        return [
            // Code art. 200 à 203 ; CCIT art. 49 : date fixée d'un commun accord.
            'CA' => $type([
                'nom' => 'Congé annuel payé',
                'categorie' => CategorieTypeConge::CONGE->value,
                'famille' => FamilleDemande::CONGE_PAYE->value,
                'droit_annuel' => 30,
                'justificatif_requis' => false,
                'autorisation_prealable_requise' => true,
                'de_droit' => false,
            ]),
            // CCIT art. 50 : autorisation écrite préalable sauf force majeure, pièce dans les 8 jours.
            'PERM-EVT' => $type([
                'nom' => 'Permission exceptionnelle pour événement familial',
                'famille' => FamilleDemande::PERMISSION->value,
                'autorisation_prealable_requise' => true,
                'delai_justification_jours' => 8,
                'delai_justification_depart' => 'evenement',
                'portee_duree_max' => 'par événement',
            ]),
            // CCIT art. 51 : 10 jours par an, plein salaire, enfant de 21 ans révolus au plus.
            'ENF-MAL' => $type([
                'nom' => 'Absence pour enfant malade',
                'famille' => FamilleDemande::FAMILLE->value,
                'plafond_valeur' => 10,
                'plafond_unite' => 'jour',
                'plafond_periode' => PeriodePlafond::ANNEE_CIVILE->value,
                'plafond_base' => BasePlafond::ENFANT->value,
            ]),
            // CCIT art. 51 : 1 mois rémunéré si le salarié le désire, prime d'assistance.
            'VEUVAGE' => $type([
                'nom' => 'Absence pour veuvage ou décès d\'un descendant direct',
                'famille' => FamilleDemande::FAMILLE->value,
                'portee_duree_max' => 'par événement',
            ]),
            // Code art. 190 ; CCIT art. 49 d : couches assimilées pour l'acquisition du congé.
            'MAT' => $type([
                'nom' => 'Congé de maternité',
                'categorie' => CategorieTypeConge::CONGE->value,
                'famille' => FamilleDemande::MATERNITE->value,
                'duree_max' => 98,
                'portee_duree_max' => 'par événement',
            ]),
            // Code art. 191 : 1 heure par jour de travail pendant 15 mois.
            'ALLAIT' => $type([
                'nom' => 'Repos pour allaitement',
                'famille' => FamilleDemande::MATERNITE->value,
                'unite' => UniteConge::HEURE->value,
            ]),
            // CCIT art. 14, 16 ; Code art. 69, 200 g : information sous 72 h, certificat sous 6 jours.
            'MAL' => $type([
                'nom' => 'Congé maladie',
                'categorie' => CategorieTypeConge::CONGE->value,
                'famille' => FamilleDemande::MALADIE->value,
                'traitement_salarial' => TraitementSalarial::PARTIEL->value,
                'delai_information_heures' => 72,
                'delai_justification_jours' => 6,
                'delai_justification_depart' => 'debut_absence',
            ]),
            // CCIT art. 15 ; Code art. 65 j : accord exprès, 5 ans d'ancienneté.
            'SANS-SOLDE' => $type([
                'nom' => 'Congé sans solde',
                'categorie' => CategorieTypeConge::CONGE->value,
                'famille' => FamilleDemande::SANS_SOLDE->value,
                'remunere' => false,
                'justificatif_requis' => false,
                'traitement_salarial' => TraitementSalarial::NON_REMUNERE->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'impact_anciennete' => ImpactAnciennete::SUSPENDUE->value,
                'autorisation_prealable_requise' => true,
                'de_droit' => false,
                'anciennete_min_mois' => 60,
            ]),
            // CCIT art. 6 §1 : convocation nominative 3 jours ouvrés avant, 6 mois d'ancienneté.
            'SYN-ASSEMB' => $type([
                'nom' => 'Assemblée statutaire syndicale',
                'famille' => FamilleDemande::SYNDICAL->value,
                'autorisation_prealable_requise' => true,
                'delai_depot' => 3,
                'delai_depot_unite' => 'ouvres',
                'anciennete_min_mois' => 6,
            ]),
            // CCIT art. 49 a : séminaires syndicaux assimilés pour l'acquisition du congé.
            'SYN-SEMIN' => $type([
                'nom' => 'Séminaire syndical',
                'famille' => FamilleDemande::SYNDICAL->value,
            ]),
            // CCIT art. 6 §2, §4, §5 : payée, non comptée pour le droit au congé payé.
            'SYN-COMM' => $type([
                'nom' => 'Commission paritaire ou réunion syndicale',
                'famille' => FamilleDemande::SYNDICAL->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
            ]),
            // CCIT art. 6 §3, §5 : représentants mandatés, 10 jours par an.
            'SYN-CONGRES' => $type([
                'nom' => 'Congrès syndical',
                'famille' => FamilleDemande::SYNDICAL->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'plafond_valeur' => 10,
                'plafond_unite' => 'jour',
                'plafond_periode' => PeriodePlafond::ANNEE_CIVILE->value,
                'plafond_base' => BasePlafond::SALARIE->value,
            ]),
            // CCIT art. 6 §6 : aucun délai opposable en cas d'urgence.
            'SYN-PARIT' => $type([
                'nom' => 'Organisme paritaire ou assesseur au tribunal du travail',
                'famille' => FamilleDemande::SYNDICAL->value,
            ]),
            // CCIT art. 6 §7 : 30 heures par mois, ni cumulées ni prises en continu.
            'SYN-CREDIT' => $type([
                'nom' => 'Crédit d\'heures des responsables confédéraux',
                'famille' => FamilleDemande::SYNDICAL->value,
                'unite' => UniteConge::HEURE->value,
                'justificatif_requis' => false,
                'plafond_valeur' => 30,
                'plafond_unite' => 'heure',
                'plafond_periode' => PeriodePlafond::MOIS->value,
                'plafond_base' => BasePlafond::SALARIE->value,
                'prise_continue_autorisee' => false,
                'report_credit_autorise' => false,
            ]),
            // CCIT art. 59 : 30 minutes par jour, 15 heures par mois, sur l'horaire normal.
            'DELEG' => $type([
                'nom' => 'Heures de délégation du personnel',
                'famille' => FamilleDemande::SYNDICAL->value,
                'unite' => UniteConge::HEURE->value,
                'justificatif_requis' => false,
                'plafond_valeur' => 15,
                'plafond_unite' => 'heure',
                'plafond_periode' => PeriodePlafond::MOIS->value,
                'plafond_base' => BasePlafond::SALARIE->value,
                'report_credit_autorise' => false,
            ]),
            // CCIT art. 6 §8 ; Code art. 65 k : sans solde, d'accord parties.
            'SYN-MANDAT' => $type([
                'nom' => 'Congé pour mandat syndical',
                'categorie' => CategorieTypeConge::CONGE->value,
                'famille' => FamilleDemande::SYNDICAL->value,
                'remunere' => false,
                'traitement_salarial' => TraitementSalarial::NON_REMUNERE->value,
                'impact_conge_annuel' => ImpactCongeAnnuel::SUSPEND->value,
                'autorisation_prealable_requise' => true,
                'de_droit' => false,
            ]),
        ];
    }

    /**
     * Événements des permissions exceptionnelles (CCIT art. 50) :
     * [code, libellé, lien de parenté, jours, délai de prise en mois, pièce attendue].
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: float, 4: ?int, 5: string}>
     */
    public function evenements(): array
    {
        return [
            ['DECES-CONJOINT', 'Décès du conjoint', 'conjoint', 4, null, 'Acte de décès'],
            ['DECES-ASCENDANT', 'Décès d\'un ascendant direct', 'ascendant', 4, null, 'Acte de décès'],
            ['DECES-DESCENDANT', 'Décès d\'un descendant direct', 'descendant', 4, null, 'Acte de décès'],
            ['DECES-BEAU-PARENT', 'Décès du beau-père ou de la belle-mère', 'beau_parent', 3, null, 'Acte de décès'],
            ['DECES-FRERE-SOEUR', 'Décès d\'un frère ou d\'une sœur', 'frere_soeur', 2, null, 'Acte de décès'],
            ['MARIAGE-SALARIE', 'Mariage du salarié', 'salarie', 3, null, 'Acte de mariage'],
            ['MARIAGE-PROCHE', 'Mariage d\'un enfant, d\'un frère ou d\'une sœur', 'enfant_frere_soeur', 1, null, 'Acte de mariage'],
            ['NAISSANCE', 'Naissance au foyer', 'foyer', 2, 6, 'Certificat ou acte de naissance'],
            ['BAPTEME', 'Baptême d\'un enfant', 'descendant', 1, null, 'Attestation de baptême'],
            ['DEMENAGEMENT', 'Déménagement', 'salarie', 2, null, 'Attestation des autorités'],
        ];
    }
}
