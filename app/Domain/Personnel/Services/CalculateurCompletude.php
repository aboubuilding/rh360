<?php

namespace App\Domain\Personnel\Services;

use App\Domain\Personnel\Enums\StatutDossier;
use App\Domain\Personnel\Models\Salarie;

class CalculateurCompletude
{
    /** Champs obligatoires pour qu'un dossier soit complet */
    private const CHAMPS_REQUIS = [
        'matricule', 'nom', 'prenoms', 'date_naissance',
        'telephone_principal', 'adresse',
        'numero_cnss', 'date_embauche', 'type_contrat',
    ];

    public function calculer(Salarie $salarie): StatutDossier
    {
        foreach (self::CHAMPS_REQUIS as $champ) {
            if (empty($salarie->{$champ})) {
                return StatutDossier::INCOMPLET;
            }
        }

        // Au moins 1 affectation en cours
        if (! $salarie->affectations()->where('en_cours', true)->exists()) {
            return StatutDossier::INCOMPLET;
        }

        return StatutDossier::COMPLET;
    }

    public function mettreAJour(Salarie $salarie): void
    {
        $statut = $this->calculer($salarie);
        if ($salarie->statut_dossier !== $statut) {
            $salarie->update(['statut_dossier' => $statut->value]);
        }
    }

    public function champsManquants(Salarie $salarie): array
    {
        $manquants = [];
        foreach (self::CHAMPS_REQUIS as $champ) {
            if (empty($salarie->{$champ})) {
                $manquants[] = $champ;
            }
        }
        return $manquants;
    }
}