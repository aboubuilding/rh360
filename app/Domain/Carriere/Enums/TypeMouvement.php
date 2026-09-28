<?php

namespace App\Domain\Carriere\Enums;

enum TypeMouvement: string
{
    case AFFECTATION        = 'affectation';
    case MUTATION           = 'mutation';
    case PROMOTION          = 'promotion';
    case AVANCEMENT         = 'avancement';
    case AVANCEMENT_ANTICIPE = 'avancement_anticipe';
    case RECLASSEMENT       = 'reclassement';
    case INTERIM            = 'interim';
    case DETACHEMENT        = 'detachement';
    case MISE_A_DISPOSITION = 'mise_a_disposition';

    public function libelle(): string
    {
        return match ($this) {
            self::AFFECTATION         => 'Affectation',
            self::MUTATION            => 'Mutation',
            self::PROMOTION           => 'Promotion',
            self::AVANCEMENT          => 'Avancement',
            self::AVANCEMENT_ANTICIPE => 'Avancement anticipé',
            self::RECLASSEMENT        => 'Reclassement',
            self::INTERIM             => 'Intérim',
            self::DETACHEMENT         => 'Détachement',
            self::MISE_A_DISPOSITION  => 'Mise à disposition',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::AFFECTATION         => 'info',
            self::MUTATION            => 'primary',
            self::PROMOTION           => 'success',
            self::AVANCEMENT          => 'success',
            self::AVANCEMENT_ANTICIPE => 'warning',
            self::RECLASSEMENT        => 'info',
            self::INTERIM             => 'secondary',
            self::DETACHEMENT         => 'secondary',
            self::MISE_A_DISPOSITION  => 'secondary',
        };
    }

    public function estCarriere(): bool
    {
        return in_array($this, [
            self::PROMOTION, self::AVANCEMENT, self::AVANCEMENT_ANTICIPE, self::RECLASSEMENT,
        ], true);
    }

    public function estAffectation(): bool
    {
        return in_array($this, [self::AFFECTATION, self::MUTATION], true);
    }

    public function estTemporaire(): bool
    {
        return in_array($this, [self::INTERIM, self::DETACHEMENT, self::MISE_A_DISPOSITION], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($e) => [$e->value => $e->libelle()])->all();
    }
}