<?php

namespace App\Domain\Carriere\Enums;

/**
 * Motif du départ d'un emploi antérieur dans l'entreprise. Seuls certains motifs permettent de
 * cumuler l'ancienneté en cas de réembauche (CCIT art. 40).
 */
enum MotifDepart: string
{
    case COMPRESSION = 'compression';
    case SUPPRESSION_EMPLOI = 'suppression_emploi';
    case FERMETURE_DRAPEAUX = 'fermeture_drapeaux';
    case SERVICE_MILITAIRE = 'service_militaire';
    case DEMISSION = 'demission';
    case LICENCIEMENT = 'licenciement';
    case FIN_CDD = 'fin_cdd';
    case AUTRE = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::COMPRESSION => 'Compression de personnel',
            self::SUPPRESSION_EMPLOI => 'Suppression d\'emploi',
            self::FERMETURE_DRAPEAUX => 'Fermeture de l\'établissement (départ de l\'employeur sous les drapeaux)',
            self::SERVICE_MILITAIRE => 'Service militaire ou paramilitaire du salarié',
            self::DEMISSION => 'Démission',
            self::LICENCIEMENT => 'Licenciement',
            self::FIN_CDD => 'Fin de contrat à durée déterminée',
            self::AUTRE => 'Autre motif',
        };
    }

    // La période antérieure s'ajoute à l'ancienneté en cas de réembauche.
    public function cumulable(): bool
    {
        return in_array($this, [self::COMPRESSION, self::SUPPRESSION_EMPLOI, self::FERMETURE_DRAPEAUX, self::SERVICE_MILITAIRE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}
