<?php

namespace App\Domain\Recrutement\Enums;

enum DecisionCandidat: string
{
    case EN_ATTENTE = 'en_attente';
    case RETENU     = 'retenu';
    case REFUSE     = 'refuse';
    case LISTE_ATTENTE = 'liste_attente';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE   => 'En attente',
            self::RETENU       => 'Retenu',
            self::REFUSE       => 'Refusé',
            self::LISTE_ATTENTE => 'Liste d\'attente',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE    => 'secondary',
            self::RETENU        => 'success',
            self::REFUSE        => 'danger',
            self::LISTE_ATTENTE => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}