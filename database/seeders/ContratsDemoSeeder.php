<?php

namespace Database\Seeders;

use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Contrats\Actions\CreerContrat;
use App\Domain\Contrats\Enums\StatutContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Personnel\Models\Salarie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContratsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseId = 1;

        $salaries = Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->limit(5)
            ->get();

        if ($salaries->isEmpty()) {
            $this->command->warn('Aucun salarié trouvé. Exécutez SalariesDemoSeeder d\'abord.');
            return;
        }

        $poste = Poste::where('entreprise_id', $entrepriseId)->first();
        $position = PositionClassification::first();

        if (! $poste || ! $position) {
            $this->command->warn('Aucun poste ou position. Exécutez les seeders d\'organisation d\'abord.');
            return;
        }

        // Action avec dépendances
        $action = app(CreerContrat::class);

        // Sauvegarde de l'utilisateur courant pour l'Action (qui utilise auth()->user())
        $superAdmin = \App\Domain\Administration\Models\Utilisateur::first();

        foreach ($salaries as $i => $salarie) {
            // Éviter les doublons
            if (Contrat::where('salarie_id', $salarie->id)->exists()) {
                continue;
            }

            $annee = now()->format('Y');
            $reference = sprintf('CTR-%s-%04d', $annee, $i + 1);

            $action->executer([
                'entreprise_id' => $entrepriseId,
                'salarie_id' => $salarie->id,
                'reference' => $reference,
                'type_contrat' => $salarie->type_contrat ?? TypeContrat::CDI->value,
                'date_debut' => $salarie->date_embauche ?? now()->subYear(),
                'date_fin' => $salarie->date_fin_contrat,
                'poste_id' => $poste->id,
                'position_classification_id' => $position->id,
                'conditions' => [
                    'salaire_base' => 250000 + ($i * 25000),
                    'prime_transport' => 15000,
                    'prime_logement' => 30000,
                ],
                'cree_par' => $superAdmin?->id ?? 1,
            ]);

            $this->command->info("Contrat créé : {$reference} pour {$salarie->nom_complet}");
        }

        // Créer 1 exemple d'avenant sur le 1er contrat signé
        $premier = Contrat::where('entreprise_id', $entrepriseId)->first();
        if ($premier) {
            // Simuler la transition jusqu'à « signé » pour avoir un exemple réaliste
            $premier->update([
                'statut' => StatutContrat::SIGNE->value,
                'date_signature' => now()->subMonths(2),
                'reference_signee' => 'ACT-' . now()->format('Y') . '-0001',
            ]);

            $this->command->info("Contrat {$premier->reference} marqué comme signé (pour démo).");
        }
    }
}