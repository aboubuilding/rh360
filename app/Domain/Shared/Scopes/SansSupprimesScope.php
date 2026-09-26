<?php

namespace App\Domain\Shared\Scopes;

use App\Domain\Shared\Enums\Etat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SansSupprimesScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable().'.etat', '!=', Etat::SUPPRIME->value);
    }
}