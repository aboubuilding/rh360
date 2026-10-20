<?php

namespace App\Domain\Interim\Enums;

// RG-PMO-03 — réponse du salarié au projet de prêt.
enum ReponseSalarie: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTE => 'Accepté',
            self::REFUSE => 'Refusé',
        };
    }
}
