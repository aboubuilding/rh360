<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\PermissionUtilisateur;
use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Support\Facades\Cache;

class ServicePermissions
{
    private const CACHE_TTL = 300;

    public function utilisateurPeut(Utilisateur $u, string $permission): bool
    {
        if (! $u->estActif()) {
            return false;
        }

        if ($u->estSuperAdmin()) {
            return true;
        }

        // Clé versionnée : incrémenter la version (utilisateur ou matrice de l'entreprise)
        // rend immédiatement obsolètes toutes les entrées, quel que soit le driver de cache.
        $cacheKey = sprintf(
            'perm:e%d:v%d:u%d:v%d:%s',
            $u->entreprise_id,
            $this->version("perm:e:{$u->entreprise_id}:version"),
            $u->id,
            $this->version("perm:u:{$u->id}:version"),
            $permission,
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($u, $permission) {
            $exception = PermissionUtilisateur::query()
                ->where('utilisateur_id', $u->id)
                ->where('permission', $permission)
                ->first();

            if ($exception) {
                return (bool) $exception->autorise;
            }

            return PermissionRole::query()
                ->where('entreprise_id', $u->entreprise_id)
                ->where('role', $u->role)
                ->where('permission', $permission)
                ->where('autorise', true)
                ->exists();
        });
    }

    /**
     * Invalide les permissions en cache d'un utilisateur (rôle, état ou exceptions modifiés).
     */
    public function viderCache(Utilisateur $u): void
    {
        $this->incrementerVersion("perm:u:{$u->id}:version");
    }

    /**
     * Invalide les permissions en cache de tous les utilisateurs d'une entreprise
     * (matrice rôles × permissions modifiée).
     */
    public function viderCacheEntreprise(int $entrepriseId): void
    {
        $this->incrementerVersion("perm:e:{$entrepriseId}:version");
    }

    private function version(string $cle): int
    {
        return (int) Cache::get($cle, 0);
    }

    private function incrementerVersion(string $cle): void
    {
        Cache::forever($cle, $this->version($cle) + 1);
    }

    public function permissionsUtilisateur(Utilisateur $u): array
    {
        $rolePermissions = PermissionRole::query()
            ->where('entreprise_id', $u->entreprise_id)
            ->where('role', $u->role)
            ->where('autorise', true)
            ->pluck('permission')
            ->all();

        $exceptions = PermissionUtilisateur::query()
            ->where('utilisateur_id', $u->id)
            ->get()
            ->keyBy('permission');

        $result = collect($rolePermissions)->flip();

        foreach ($exceptions as $perm => $ex) {
            if ($ex->autorise) {
                $result[$perm] = true;
            } else {
                $result->forget($perm);
            }
        }

        return $result->keys()->all();
    }
}