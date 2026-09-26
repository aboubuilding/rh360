<?php

namespace App\Domain\Administration\Actions;

use App\Domain\Administration\Models\PermissionRole;
use Illuminate\Support\Facades\DB;

class ModifierPermissionRole
{
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

        // Note : le cache des permissions est vidé au niveau du contrôleur
        // via ServicePermissions::viderCacheRole() ou un flush global.
    }
}