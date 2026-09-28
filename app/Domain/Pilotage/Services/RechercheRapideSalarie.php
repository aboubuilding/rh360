<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Personnel\Models\Salarie;
use Illuminate\Support\Collection;

class RechercheRapideSalarie
{
    /**
     * Recherche rapide par nom, prénom ou matricule.
     * Renvoie un tableau prêt pour l'affichage (aperçu rapide).
     */
    public function rechercher(int $entrepriseId, string $terme, int $limite = 10): Collection
    {
        if (mb_strlen(trim($terme)) < 2) {
            return collect();
        }

        return Salarie::where('entreprise_id', $entrepriseId)
            ->where('actif', true)
            ->where(function ($q) use ($terme) {
                $q->where('nom', 'like', "%{$terme}%")
                  ->orWhere('prenoms', 'like', "%{$terme}%")
                  ->orWhere('matricule', 'like', "%{$terme}%");
            })
            ->with(['affectations.poste', 'affectations.structure'])
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->limit($limite)
            ->get()
            ->map(fn (Salarie $s) => [
                'id' => $s->id,
                'matricule' => $s->matricule,
                'nom_complet' => $s->nom_complet,
                'initiales' => $s->initiales,
                'poste' => $s->affectationCourante?->poste?->intitule,
                'structure' => $s->affectationCourante?->structure?->nom,
                'photo_url' => $s->chemin_photo ? route('personnel.salaries.photo', $s) : null,
                'url' => route('personnel.salaries.show', $s),
            ]);
    }

    /**
     * Aperçu rapide : renvoie les informations essentielles d'un salarié.
     */
    public function apercu(Salarie $salarie): array
    {
        $salarie->load(['affectations.poste', 'affectations.structure']);

        return [
            'id' => $salarie->id,
            'matricule' => $salarie->matricule,
            'nom_complet' => $salarie->nom_complet,
            'initiales' => $salarie->initiales,
            'date_embauche' => $salarie->date_embauche?->format('d/m/Y'),
            'anciennete' => $salarie->date_embauche?->diffForHumans(now(), true),
            'poste' => $salarie->affectationCourante?->poste?->intitule,
            'structure' => $salarie->affectationCourante?->structure?->nom,
            'statut_emploi' => $salarie->statut_emploi?->value,
            'statut_dossier' => $salarie->statut_dossier?->libelle(),
            'statut_dossier_couleur' => $salarie->statut_dossier?->couleur(),
            'photo_url' => $salarie->chemin_photo ? route('personnel.salaries.photo', $salarie) : null,
            'fiche_url' => route('personnel.salaries.show', $salarie),
        ];
    }
}