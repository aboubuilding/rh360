<?php

namespace App\Domain\Contrats\Enums;

enum ObjetPieceContrat: string
{
    case CONTRAT_SIGNE  = 'contrat_signe';
    case AVENANT_SIGNE  = 'avenant_signe';
    case CNI            = 'cni';
    case DIPLOME        = 'diplome';
    case CV             = 'cv';
    case DEROGATION     = 'derogation';
    case AUTRE          = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::CONTRAT_SIGNE => 'Contrat signé',
            self::AVENANT_SIGNE => 'Avenant signé',
            self::CNI           => 'Pièce d\'identité',
            self::DIPLOME       => 'Diplôme',
            self::CV            => 'CV',
            self::DEROGATION    => 'Dérogation',
            self::AUTRE         => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}