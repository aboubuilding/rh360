<?php

namespace Database\Seeders;

use App\Domain\Personnel\Models\Salarie;
use App\Domain\Recrutement\Enums\DecisionCandidat;
use App\Domain\Recrutement\Enums\EtapeCandidat;
use App\Domain\Recrutement\Enums\SourceCandidat;
use App\Domain\Recrutement\Enums\StatutBesoinRecrutement;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Services\GenerateurReferenceBesoin;
use Illuminate\Database\Seeder;

class RecrutementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;
        $generateur = app(GenerateurReferenceBesoin::class);

        // 2 besoins
        $besoins = [
            [
                'intitule_poste' => 'Développeur Full-Stack',
                'departement' => 'Informatique',
                'nombre_postes' => 2,
                'type_contrat' => 'CDI',
                'date_cible' => now()->addMonth(),
                'motif' => 'Croissance de l\'équipe technique',
                'statut' => StatutBesoinRecrutement::EN_COURS->value,
            ],
            [
                'intitule_poste' => 'Assistant(e) RH',
                'departement' => 'Ressources Humaines',
                'nombre_postes' => 1,
                'type_contrat' => 'CDD',
                'date_cible' => now()->addMonths(2),
                'motif' => 'Remplacement d\'un congé maternité',
                'statut' => StatutBesoinRecrutement::VALIDE->value,
            ],
        ];

        $besoinsCrees = [];
        foreach ($besoins as $b) {
            $besoin = BesoinRecrutement::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'intitule_poste' => $b['intitule_poste']],
                array_merge($b, [
                    'entreprise_id' => $entrepriseId,
                    'reference' => $generateur->generer($entrepriseId),
                    'etat' => 1,
                ])
            );
            $besoinsCrees[] = $besoin;
        }

        // 5 candidats avec des étapes variées
        $candidats = [
            ['nom' => 'AGBEKO', 'prenoms' => 'Kossi', 'email' => 'kossi.agbeko@example.tg', 'etape' => EtapeCandidat::ENTRETIEN_1->value, 'score' => 78, 'decision' => DecisionCandidat::EN_ATTENTE->value],
            ['nom' => 'MENSAH', 'prenoms' => 'Akou', 'email' => 'akou.mensah@example.tg', 'etape' => EtapeCandidat::ENTRETIEN_2->value, 'score' => 85, 'decision' => DecisionCandidat::EN_ATTENTE->value],
            ['nom' => 'KOUDJO', 'prenoms' => 'Yao', 'email' => 'yao.koudjo@example.tg', 'etape' => EtapeCandidat::OFFRE->value, 'score' => 88, 'decision' => DecisionCandidat::RETENU->value],
            ['nom' => 'SEDZRO', 'prenoms' => 'Ama', 'email' => 'ama.sedzro@example.tg', 'etape' => EtapeCandidat::CANDIDATURE_RECUE->value, 'score' => null, 'decision' => DecisionCandidat::EN_ATTENTE->value],
            ['nom' => 'DOSSEH', 'prenoms' => 'Komlan', 'email' => 'komlan.dosseh@example.tg', 'etape' => EtapeCandidat::REFUSE->value, 'score' => 45, 'decision' => DecisionCandidat::REFUSE->value],
        ];

        foreach ($candidats as $i => $c) {
            Candidat::updateOrCreate(
                ['entreprise_id' => $entrepriseId, 'email' => $c['email']],
                [
                    'entreprise_id' => $entrepriseId,
                    'besoin_id' => $besoinsCrees[$i % 2]->id,
                    'nom' => $c['nom'],
                    'prenoms' => $c['prenoms'],
                    'telephone' => '+228 9' . str_pad($i + 1, 7, '0', STR_PAD_LEFT),
                    'source' => $i % 2 === 0 ? SourceCandidat::ANNONCE->value : SourceCandidat::RECOMMANDATION->value,
                    'etape' => $c['etape'],
                    'score' => $c['score'],
                    'decision' => $c['decision'],
                    'date_entretien' => $c['score'] !== null ? now()->subDays(10 - $i) : null,
                    'observations' => $i === 4 ? 'Profil non aligné avec les exigences du poste.' : null,
                    'etat' => 1,
                ]
            );
        }

        $this->command->info('Recrutement : 2 besoins et 5 candidats créés.');
    }
}