<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les factories des modèles de domaine (App\Domain\*\Models\X) sont rangées
        // à plat dans database/factories sous Database\Factories\XFactory.
        Factory::guessFactoryNamesUsing(
            fn (string $modele) => 'Database\\Factories\\'.class_basename($modele).'Factory'
        );
    }
}
