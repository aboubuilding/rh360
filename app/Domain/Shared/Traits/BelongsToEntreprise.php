<?php

namespace App\Domain\Shared\Traits;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Shared\Scopes\EntrepriseScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEntreprise
{
    public static function bootBelongsToEntreprise(): void
    {
        static::addGlobalScope(new EntrepriseScope());

        static::creating(function ($model) {
            if (! $model->entreprise_id && auth()->check()) {
                $model->entreprise_id = auth()->user()->entreprise_id;
            }
        });
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function scopePourEntreprise($q, int $entrepriseId)
    {
        return $q->withoutGlobalScope(EntrepriseScope::class)
                 ->where($this->getTable().'.entreprise_id', $entrepriseId);
    }
}