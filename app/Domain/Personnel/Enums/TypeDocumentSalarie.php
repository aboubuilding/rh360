<?php

namespace App\Domain\Personnel\Enums;

enum TypeDocumentSalarie: string
{
    case CONTRAT          = 'Contrat';
    case DIPLOME          = 'Diplôme';
    case CERTIFICAT       = 'Certificat';
    case ATTESTATION      = 'Attestation';
    case PIECE_IDENTITE   = 'Pièce d\'identité';
    case CV               = 'CV';
    case MEDICAL          = 'Certificat médical';
    case AUTRE            = 'Autre';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->value])->all();
    }
}