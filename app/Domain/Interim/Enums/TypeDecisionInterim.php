<?php

namespace App\Domain\Interim\Enums;

/**
 * Nature d'une décision RH historisée (qui, quand, pièce jointe).
 */
enum TypeDecisionInterim: string
{
    case CREATION = 'creation';
    case PROLONGATION = 'prolongation';
    case RECLASSEMENT = 'reclassement';
    case RETOUR = 'retour';
    case CLOTURE = 'cloture';
    case REGULARISATION = 'a_regulariser';
    case AVANTAGE = 'avantage';
    case REPRISE = 'reprise';
    case CHECKLIST_SST = 'checklist_sst';
    case DEMARRAGE = 'demarrage';
    case RENOUVELLEMENT = 'renouvellement';
    case ANNULATION = 'annulation';

    public function libelle(): string
    {
        return match ($this) {
            self::CREATION => 'Création',
            self::PROLONGATION => 'Prolongation',
            self::RECLASSEMENT => 'Reclassement',
            self::RETOUR => 'Retour',
            self::CLOTURE => 'Clôture',
            self::REGULARISATION => 'Passage à régulariser',
            self::AVANTAGE => 'Avantage du poste occupé',
            self::REPRISE => 'Reprise de données',
            self::CHECKLIST_SST => 'Checklist SST validée',
            self::DEMARRAGE => 'Démarrage de la mission',
            self::RENOUVELLEMENT => 'Renouvellement',
            self::ANNULATION => 'Annulation',
        };
    }
}
