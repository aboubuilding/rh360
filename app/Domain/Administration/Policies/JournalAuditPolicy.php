<?php

namespace App\Domain\Administration\Policies;

use App\Domain\Administration\Models\Utilisateur;

class JournalAuditPolicy
{
    public function viewAny(Utilisateur $u): bool
    {
        return $u->peut('admin.audit.view');
    }
}