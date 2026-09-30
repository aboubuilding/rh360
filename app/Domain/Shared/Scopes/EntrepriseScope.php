<?php

namespace App\Domain\Shared\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EntrepriseScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // hasUser() et non user() : user() déclencherait la lecture de l'utilisateur depuis la
        // session, requête elle-même soumise à ce scope (modèle Utilisateur) → récursion infinie.
        // Le middleware « auth » résout l'utilisateur avant toute requête métier.
        $guard = auth()->guard();
        $user = $guard->hasUser() ? $guard->user() : null;

        if ($user && $user->entreprise_id) {
            $builder->where($model->getTable().'.entreprise_id', $user->entreprise_id);
        }
    }
}