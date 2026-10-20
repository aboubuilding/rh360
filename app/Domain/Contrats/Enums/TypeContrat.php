<?php

namespace App\Domain\Contrats\Enums;

enum TypeContrat: string
{
    case CDI          = 'CDI';
    case CDD          = 'CDD';
    case STAGE        = 'Stage';
    case APPRENTISSAGE= 'Apprentissage';
    case INTERIM      = 'Intérim';
    case CONSULTANT   = 'Consultant';
    case VACATAIRE    = 'Vacataire';

    public function estDureeDeterminee(): bool
    {
        return in_array($this, [self::CDD, self::STAGE, self::APPRENTISSAGE, self::INTERIM, self::VACATAIRE], true);
    }

    /**
     * RG-TT-03 / RG-TT-04 — Un travailleur intérimaire reste salarié de l'agence, seul employeur :
     * il ne reçoit pas de contrat de l'entreprise utilisatrice (ni effectif, ni paie). Le type
     * « Intérim » est conservé pour les contrats existants, mais refusé à la saisie.
     */
    public static function regleHorsInterim(): \Closure
    {
        return function (string $attribut, mixed $valeur, \Closure $echec) {
            if ($valeur === self::INTERIM->value) {
                $echec('Contrat refusé : un travailleur intérimaire reste salarié de l\'entreprise de travail temporaire, '
                    .'seul employeur. Enregistrer une mission d\'intérim.');
            }
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}