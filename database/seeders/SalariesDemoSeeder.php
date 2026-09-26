<?php

namespace Database\Seeders;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;

class SalariesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        // Récupérer les structures et postes existants
        $structures = Structure::where('entreprise_id', $entrepriseId)->get();
        $postes = Poste::where('entreprise_id', $entrepriseId)->get();

        if ($structures->isEmpty() || $postes->isEmpty()) {
            $this->command->warn('Aucune structure/poste trouvé. Exécutez TypeStructureSeeder d\'abord.');
            return;
        }

        $salaries = [
            [
                'matricule' => 'MAT-2026-0001',
                'nom' => 'DOSSOU',
                'prenoms' => 'Kofi',
                'sexe' => 'M',
                'date_naissance' => '1985-03-12',
                'lieu_naissance' => 'Lomé',
                'nationalite' => 'Togolaise',
                'telephone_principal' => '90123456',
                'email_personnel' => 'kofi.dossou@example.tg',
                'adresse' => 'Quartier Tokoin, Rue 12',
                'ville' => 'Lomé',
                'situation_matrimoniale' => 'Marié(e)',
                'numero_cnss' => 'CNSS-100234567',
                'banque' => 'Ecobank',
                'compte_bancaire' => 'TG53 0001 0002 0003 0004 5678',
                'mode_paiement' => 'Virement',
                'date_embauche' => '2020-01-06',
                'date_prise_service' => '2020-02-01',
                'type_contrat' => 'CDI',
                'reference_contrat' => 'CTR-2020-0001',
                'date_contrat' => '2020-01-06',
                'lieu_affectation' => 'Siège',
                'statut_emploi' => StatutEmploi::ACTIF->value,
            ],
            [
                'matricule' => 'MAT-2026-0002',
                'nom' => 'AMEGAN',
                'prenoms' => 'Ama',
                'sexe' => 'F',
                'date_naissance' => '1990-07-22',
                'lieu_naissance' => 'Kpalimé',
                'nationalite' => 'Togolaise',
                'telephone_principal' => '91234567',
                'email_personnel' => 'ama.amegan@example.tg',
                'adresse' => 'Quartier Bè, Avenue 5',
                'ville' => 'Lomé',
                'situation_matrimoniale' => 'Célibataire',
                'numero_cnss' => 'CNSS-100234568',
                'numero_amu' => 'AMU-234567',
                'banque' => 'Orabank',
                'compte_bancaire' => 'TG53 0002 0003 0004 0005 6789',
                'mode_paiement' => 'Virement',
                'date_embauche' => '2021-03-15',
                'date_prise_service' => '2021-04-01',
                'type_contrat' => 'CDI',
                'reference_contrat' => 'CTR-2021-0015',
                'date_contrat' => '2021-03-15',
                'lieu_affectation' => 'Siège',
                'statut_emploi' => StatutEmploi::ACTIF->value,
            ],
            [
                'matricule' => 'MAT-2026-0003',
                'nom' => 'KOFFI',
                'prenoms' => 'Yao',
                'sexe' => 'M',
                'date_naissance' => '1995-11-05',
                'lieu_naissance' => 'Sokodé',
                'nationalite' => 'Togolaise',
                'telephone_principal' => '92345678',
                'email_personnel' => 'yao.koffi@example.tg',
                'adresse' => 'Quartier Agoè, Rue 8',
                'ville' => 'Lomé',
                'situation_matrimoniale' => 'Célibataire',
                'numero_cnss' => 'CNSS-100234569',
                'banque' => 'BSIC',
                'compte_bancaire' => 'TG53 0003 0004 0005 0006 7890',
                'mode_paiement' => 'Virement',
                'date_embauche' => '2022-06-01',
                'date_prise_service' => '2022-06-15',
                'type_contrat' => 'CDD',
                'reference_contrat' => 'CTR-2022-0042',
                'date_contrat' => '2022-06-01',
                'date_fin_contrat' => '2024-05-31',
                'lieu_affectation' => 'Agence',
                'statut_emploi' => StatutEmploi::ACTIF->value,
            ],
            [
                'matricule' => 'MAT-2026-0004',
                'nom' => 'SEDZRO',
                'prenoms' => 'Akouvi',
                'sexe' => 'F',
                'date_naissance' => '1993-02-18',
                'lieu_naissance' => 'Aného',
                'nationalite' => 'Togolaise',
                'telephone_principal' => '93456789',
                'email_personnel' => 'akouvi.sedzro@example.tg',
                'adresse' => 'Quartier Adidogomé, Rue 3',
                'ville' => 'Lomé',
                'situation_matrimoniale' => 'Marié(e)',
                'numero_cnss' => 'CNSS-100234570',
                'banque' => 'Ecobank',
                'mode_paiement' => 'Virement',
                'date_embauche' => '2019-09-02',
                'date_prise_service' => '2019-09-02',
                'type_contrat' => 'CDI',
                'reference_contrat' => 'CTR-2019-0088',
                'date_contrat' => '2019-09-02',
                'lieu_affectation' => 'Siège',
                'statut_emploi' => StatutEmploi::ACTIF->value,
            ],
            [
                'matricule' => 'MAT-2026-0005',
                'nom' => 'GNASSINGBE',
                'prenoms' => 'Komlan',
                'sexe' => 'M',
                'date_naissance' => '1988-06-30',
                'lieu_naissance' => 'Atakpamé',
                'nationalite' => 'Togolaise',
                'telephone_principal' => '94567890',
                'email_personnel' => 'komlan.gnassingbe@example.tg',
                'adresse' => 'Quartier Kégué, Rue 15',
                'ville' => 'Lomé',
                'situation_matrimoniale' => 'Marié(e)',
                'numero_cnss' => 'CNSS-100234571',
                'banque' => 'Orabank',
                'mode_paiement' => 'Virement',
                'date_embauche' => '2018-04-10',
                'date_prise_service' => '2018-04-10',
                'type_contrat' => 'CDI',
                'reference_contrat' => 'CTR-2018-0033',
                'date_contrat' => '2018-04-10',
                'lieu_affectation' => 'Siège',
                'statut_emploi' => StatutEmploi::ACTIF->value,
            ],
        ];

        foreach ($salaries as $i => $donnees) {
            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['numero_enregistrement'] = 'ENR-2026-' . str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            $donnees['statut_dossier'] = StatutDossier::COMPLET->value;
            $donnees['origine_carriere'] = 'manual';
            $donnees['actif'] = true;
            $donnees['etat'] = 1;

            $salarie = Salarie::create($donnees);

            // Créer une affectation
            $structure = $structures->random();
            $poste = $postes->where('structure_id', $structure->id)->first()
                ?? $postes->first();

            Affectation::create([
                'salarie_id' => $salarie->id,
                'structure_id' => $structure->id,
                'poste_id' => $poste->id,
                'date_debut' => $salarie->date_prise_service ?? $salarie->date_embauche,
                'en_cours' => true,
                'etat' => 1,
            ]);
        }

        $this->command->info(count($salaries) . ' salariés de démonstration créés.');
    }
}