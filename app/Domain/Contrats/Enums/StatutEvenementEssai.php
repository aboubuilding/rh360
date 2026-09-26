<?php

namespace App\Domain\Contrats\Enums;

enum StatutEvenementEssai: string
{
    case EN_ATTENTE = 'pending';
    case VALIDE     = 'approved';
    case REFUSE     = 'rejected';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDE     => 'Validé',
            self::REFUSE     => 'Refusé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'warning',
            self::VALIDE     => 'success',
            self::REFUSE     => 'danger',
        };
    }
}