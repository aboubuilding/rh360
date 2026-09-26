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

        $cacheKey = "perm:u:{$u->id}:{$permission}";

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

    public function viderCache(Utilisateur $u): void
    {
        Cache::forget("perm:u:{$u->id}:*");
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