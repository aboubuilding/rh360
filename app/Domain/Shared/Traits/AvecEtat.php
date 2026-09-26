<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Enums\Etat;
use App\Domain\Shared\Scopes\SansSupprimesScope;
use Illuminate\Database\Eloquent\Builder;

trait AvecEtat
{
    public static function bootAvecEtat(): void
    {
        static::addGlobalScope(new SansSupprimesScope());
    }

    public function initializeAvecEtat(): void
    {
        if (! isset($this->casts['etat'])) {
            $this->casts['etat'] = Etat::class;
        }
        if (! isset($this->attributes['etat'])) {
            $this->attributes['etat'] = Etat::ACTIF->value;
        }
    }

    public function scopeActifs(Builder $q): Builder
    {
        return $q->where($this->getTable().'.etat', Etat::ACTIF->value);
    }

    public function scopeInactifs(Builder $q): Builder
    {
        return $q->where($this->getTable().'.etat', Etat::INACTIF->value);
    }

    public function scopeSupprimes(Builder $q): Builder
    {
        return $q->where($this->getTable().'.etat', Etat::SUPPRIME->value);
    }

    public function scopeAvecSupprimes(Builder $q): Builder
    {
        return $q->withoutGlobalScope(SansSupprimesScope::class);
    }

    public function marquerActif(): void    { $this->update(['etat' => Etat::ACTIF]); }
    public function marquerInactif(): void  { $this->update(['etat' => Etat::INACTIF]); }
    public function marquerSupprime(): void { $this->update(['etat' => Etat::SUPPRIME]); }
    public function restaurer(): void       { $this->update(['etat' => Etat::ACTIF]); }

    public function estActif(): bool    { return $this->etat === Etat::ACTIF; }
    public function estInactif(): bool  { return $this->etat === Etat::INACTIF; }
    public function estSupprime(): bool { return $this->etat === Etat::SUPPRIME; }

    public function getEtatLibelleAttribute(): string
    {
        return $this->etat instanceof Etat ? $this->etat->libelle() : '';
    }
}