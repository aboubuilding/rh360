<?php

namespace Database\Seeders;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Models\TypeStructure;
use Illuminate\Database\Seeder;

class OrganisationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $types = TypeStructure::where('entreprise_id', $entrepriseId)->pluck('id', 'code');

        if ($types->isEmpty()) {
            $this->command?->warn('Aucun type de structure trouvé. Exécutez TypeStructureSeeder d\'abord.');
            return;
        }

        $dg = Structure::updateOrCreate(
            ['entreprise_id' => $entrepriseId, 'code' => 'DG'],
            ['type_structure_id' => $types['DG'], 'nom' => 'Direction générale', 'localisation' => 'Siège', 'actif' => true, 'etat' => 1]
        );

        $structures = [
            ['code' => 'DRH', 'nom' => 'Direction des ressources humaines', 'type' => 'DIR'],
            ['code' => 'DAF', 'nom' => 'Direction administrative et financière', 'type' => 'DIR'],
            ['code' => 'DTE', 'nom' => 'Direction technique', 'type' => 'DIR'],
        ];

        $postes = [
            'DG' => [['code' => 'P-DG', 'intitule' => 'Directeur général', 'categorie' => 'Cadre']],
            'DRH' => [
                ['code' => 'P-DRH', 'intitule' => 'Directeur des ressources humaines', 'categorie' => 'Cadre'],
                ['code' => 'P-GRH', 'intitule' => 'Gestionnaire RH', 'categorie' => 'Agent de maîtrise'],
            ],
            'DAF' => [
                ['code' => 'P-CPT', 'intitule' => 'Comptable', 'categorie' => 'Agent de maîtrise'],
                ['code' => 'P-SEC', 'intitule' => 'Secrétaire', 'categorie' => 'Employé'],
            ],
            'DTE' => [
                ['code' => 'P-ING', 'intitule' => 'Ingénieur', 'categorie' => 'Cadre'],
                ['code' => 'P-TEC', 'intitule' => 'Technicien', 'categorie' => 'Employé'],
            ],
        ];

        $parStructure = ['DG' => $dg];

        foreach ($structures as $s) {
            $parStructure[$s['code']] = Structure::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'code' => $s['code']],
                ['type_structure_id' => $types[$s['type']], 'parent_id' => $dg->id, 'nom' => $s['nom'], 'localisation' => 'Siège', 'actif' => true, 'etat' => 1]
            );
        }

        foreach ($postes as $codeStructure => $liste) {
            foreach ($liste as $p) {
                Poste::updateOrCreate(
                    ['entreprise_id' => $entrepriseId, 'code' => $p['code']],
                    [
                        'structure_id' => $parStructure[$codeStructure]->id,
                        'intitule' => $p['intitule'],
                        'categorie' => $p['categorie'],
                        'effectif_cible' => 2,
                        'actif' => true,
                        'etat' => 1,
                    ]
                );
            }
        }
    }
}
