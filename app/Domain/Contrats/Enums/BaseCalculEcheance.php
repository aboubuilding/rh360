<?php

namespace App\Domain\Contrats\Enums;

enum BaseCalculEcheance: string
{
    case DATE_DEBUT  = 'date_debut';
    case DATE_FIN    = 'date_fin';
    case DATE_SIGNATURE = 'date_signature';
    case DATE_ESSAI_FIN = 'date_essai_fin';

    public function libelle(): string
    {
        return match ($this) {
            self::DATE_DEBUT    => 'Date de début',
            self::DATE_FIN      => 'Date de fin',
            self::DATE_SIGNATURE => 'Date de signature',
            self::DATE_ESSAI_FIN => 'Fin de période d\'essai',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}