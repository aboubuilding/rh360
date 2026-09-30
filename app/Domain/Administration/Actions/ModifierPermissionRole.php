<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Services\ServicePermissions;
use Illuminate\Support\Facades\DB;

class ModifierPermissionRole
{
    public function __construct(private ServicePermissions $permissions) {}

    /**
     * @param string $role
     * @param array<string, bool> $permissions  ex. ['salaries.view' => true, 'salaries.manage' => false]
     */
    public function executer(int $entrepriseId, string $role, array $permissions): void
    {
        DB::transaction(function () use ($entrepriseId, $role, $permissions) {
            foreach ($permissions as $permission => $autorise) {
                PermissionRole::updateOrCreate(
                    [
                        'entreprise_id' => $entrepriseId,
                        'role' => $role,
                        'permission' => $permission,
                    ],
                    ['autorise' => $autorise]
                );
            }
        });

        // Effet immédiat pour tous les utilisateurs de l'entreprise
        $this->permissions->viderCacheEntreprise($entrepriseId);
    }
}