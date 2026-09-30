<?php

/*
| CDC §3 / §5 — Le Super administrateur dispose de tous les droits : il doit
| pouvoir ouvrir chaque écran (GET) de l'application, sur tous les modules.
*/

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;

beforeEach(function () {
    $this->seed();
    $this->superAdmin = Utilisateur::where('role', Utilisateur::ROLE_SUPER_ADMIN)->first();

    // Un acte de carrière encore modifiable, pour couvrir son écran de modification
    \App\Domain\Carriere\Models\MouvementCarriere::factory()->create([
        'entreprise_id' => 1,
        'salarie_id' => \App\Domain\Personnel\Models\Salarie::withoutGlobalScopes()->value('id'),
    ]);
});

/**
 * Construit l'URL d'une route GET en résolvant ses paramètres sur les données de démonstration.
 * Retourne null si un paramètre ne peut pas être résolu (aucune donnée correspondante).
 */
function urlPourSuperAdmin(Route $route): ?string
{
    $parametres = [];
    $action = $route->getActionName();
    $reflexion = str_contains($action, '@') && method_exists(...explode('@', $action))
        ? new ReflectionMethod(...explode('@', $action))
        : null;

    foreach ($route->parameterNames() as $nom) {
        $type = collect($reflexion?->getParameters() ?? [])
            ->first(fn ($p) => $p->getName() === $nom)?->getType();

        if ($type instanceof ReflectionNamedType && is_subclass_of($type->getName(), Model::class)) {
            $instance = new ($type->getName());
            $schema = $instance->getConnection()->getSchemaBuilder();
            $requete = $type->getName()::query()->withoutGlobalScopes();

            if ($schema->hasColumn($instance->getTable(), 'entreprise_id')) {
                $requete->where('entreprise_id', 1);
            }
            // Choisir une fiche dans l'état que l'écran exige : un avenant se crée sur un contrat
            // signé, une modification porte sur une fiche encore en brouillon.
            if ($schema->hasColumn($instance->getTable(), 'statut')) {
                $statutVoulu = str_contains($route->getName(), 'avenant') ? 'signed' : 'draft';
                $requete->orderByRaw('case when statut = ? then 0 else 1 end', [$statutVoulu]);
            }
            $modele = $requete->first();
            if (! $modele) {
                return null;
            }
            $parametres[$nom] = $modele->getRouteKey();
        } else {
            $parametres[$nom] = 1; // paramètre scalaire (ex. numéro d'étape)
        }
    }

    return route($route->getName(), $parametres, false);
}

it('ouvre tous les écrans de l\'application pour le Super administrateur', function () {
    $this->actingAs($this->superAdmin);

    $echecs = [];
    $testees = 0;

    foreach (app('router')->getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true) || ! $route->getName()) {
            continue;
        }
        if (! in_array('auth', $route->gatherMiddleware(), true)
            || in_array($route->getName(), ['logout'], true)) {
            continue;
        }

        $url = urlPourSuperAdmin($route);
        if ($url === null) {
            continue;
        }

        $statut = $this->get($url)->getStatusCode();
        $testees++;

        // 200, redirection ou 404 métier (fichier absent) sont acceptables ; jamais 403 ni 5xx
        if ($statut === 403 || $statut >= 500) {
            $echecs[] = "{$statut} {$route->getName()} ({$url})";
        }
    }

    expect($testees)->toBeGreaterThan(80);
    expect($echecs)->toBe([]);
});

it('dispose de toutes les permissions du catalogue, y compris sensibles', function () {
    foreach (\Database\Seeders\PermissionSeeder::catalogue() as $permission) {
        expect($this->superAdmin->peut($permission))->toBeTrue($permission);
    }
});

it('conserve tous les droits même si une exception individuelle tente de les retirer', function () {
    \App\Domain\Administration\Models\PermissionUtilisateur::create([
        'entreprise_id' => 1, 'utilisateur_id' => $this->superAdmin->id,
        'permission' => 'salaries.view', 'autorise' => false,
    ]);

    expect($this->superAdmin->fresh()->peut('salaries.view'))->toBeTrue();
});
