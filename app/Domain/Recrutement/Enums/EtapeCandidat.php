<?php

namespace App\Domain\Recrutement\Enums;

enum EtapeCandidat: string
{
    case CANDIDATURE_RECUE = 'candidature_recue';
    case PRESELECTION      = 'preselection';
    case ENTRETIEN_1       = 'entretien_1';
    case ENTRETIEN_2       = 'entretien_2';
    case TEST              = 'test';
    case OFFRE             = 'offre';
    case ACCEPTE           = 'accepte';
    case REFUSE            = 'refuse';
    case RETIRE            = 'retire';

    public function libelle(): string
    {
        return match ($this) {
            self::CANDIDATURE_RECUE => 'Candidature reçue',
            self::PRESELECTION      => 'Présélection',
            self::ENTRETIEN_1       => 'Entretien 1',
            self::ENTRETIEN_2       => 'Entretien 2',
            self::TEST              => 'Test / évaluation',
            self::OFFRE             => 'Offre envoyée',
            self::ACCEPTE           => 'Accepté',
            self::REFUSE            => 'Refusé',
            self::RETIRE            => 'Retiré',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::CANDIDATURE_RECUE => 'secondary',
            self::PRESELECTION      => 'info',
            self::ENTRETIEN_1       => 'primary',
            self::ENTRETIEN_2       => 'primary',
            self::TEST              => 'primary',
            self::OFFRE             => 'warning',
            self::ACCEPTE           => 'success',
            self::REFUSE            => 'danger',
            self::RETIRE            => 'dark',
        };
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::ACCEPTE, self::REFUSE, self::RETIRE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}