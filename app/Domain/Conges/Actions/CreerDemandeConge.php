<?php

namespace App\Domain\Conges\Actions;

use App\Domain\Conges\Enums\StatutDemandeConge;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Services\CalculateurDureeConge;
use App\Domain\Conges\Services\GenerateurNumeroDemande;
use Illuminate\Support\Facades\DB;

class CreerDemandeConge
{
    public function __construct(
        private GenerateurNumeroDemande $numeroteur,
        private CalculateurDureeConge $calculateurDuree,
    ) {}

    public function executer(array $donnees): DemandeConge
    {
        return DB::transaction(function () use ($donnees) {
            $entrepriseId = auth()->user()->entreprise_id;
            $type = \App\Domain\Conges\Models\TypeConge::findOrFail($donnees['type_conge_id']);

            // Calcul automatique de la durée et de la date de reprise
            $debut = \Carbon\Carbon::parse($donnees['date_debut']);

            if (! empty($donnees['date_reprise'])) {
                $reprise = \Carbon\Carbon::parse($donnees['date_reprise']);
                $duree = $this->calculateurDuree->calculer($debut, $reprise, $type);
            } else {
                $duree = (float) ($donnees['duree_jours'] ?? 0);
                $reprise = $this->calculateurDuree->calculerDateReprise($debut, $duree, $type);
            }

            $donnees['entreprise_id'] = $entrepriseId;
            $donnees['numero_demande'] = $donnees['numero_demande']
                ?? $this->numeroteur->generer($entrepriseId);
            $donnees['date_demande'] = $donnees['date_demande'] ?? now();
            $donnees['date_reprise'] = $reprise;
            $donnees['duree_jours'] = $duree;
            $donnees['statut'] = StatutDemandeConge::BROUILLON->value;
            $donnees['cree_par'] = auth()->id();
            $donnees['etat'] = 1;

            return DemandeConge::create($donnees);
        });
    }
}