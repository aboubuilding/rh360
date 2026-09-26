<?php

namespace App\Domain\Personnel\Actions;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Models\Affectation;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Services\CalculateurCompletude;
use App\Domain\Personnel\Services\GenerateurMatricule;
use App\Domain\Personnel\Services\GenerateurNumeroEnregistrement;
use Illuminate\Support\Facades\DB;

class CreerSalarie
{
    public function __construct(
        private GenerateurMatricule $matricule,
        private GenerateurNumeroEnregistrement $numero,
        private CalculateurCompletude $completude,
    ) {}

    public function executer(array $donnees): Salarie
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;

            // Générer matricule et n° enregistrement si absents
            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['matricule'] = $donnees['matricule'] ?? $this->matricule->generer($entrepriseId);
            $donnees['numero_enregistrement'] = $donnees['numero_enregistrement']
                ?? $this->numero->generer($entrepriseId);
            $donnees['statut_emploi'] = $donnees['statut_emploi'] ?? StatutEmploi::ACTIF->value;
            $donnees['statut_dossier'] = StatutDossier::INCOMPLET->value;
            $donnees['actif'] = true;
            $donnees['etat'] = 1;

            $salarie = Salarie::create($donnees);

            // Créer l'affectation initiale si les données sont présentes
            if (! empty($donnees['structure_id']) && ! empty($donnees['poste_id'])) {
                Affectation::create([
                    'salarie_id' => $salarie->id,
                    'structure_id' => $donnees['structure_id'],
                    'poste_id' => $donnees['poste_id'],
                    'date_debut' => $donnees['date_prise_service'] ?? now(),
                    'en_cours' => true,
                    'etat' => 1,
                ]);
            }

            // Recalculer la complétude
            $this->completude->mettreAJour($salarie->fresh());

            return $salarie->fresh(['affectations', 'membresFoyer']);
        });
    }
}