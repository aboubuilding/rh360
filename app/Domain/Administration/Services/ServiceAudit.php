<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Models\JournalAudit;
use Illuminate\Database\Eloquent\Model;

class ServiceAudit
{
    public function tracer(string $action, Model $modele, ?array $details = null): void
    {
        $user = auth()->user();

        JournalAudit::create([
            'entreprise_id' => $modele->entreprise_id ?? $user?->entreprise_id,
            'utilisateur_id' => $user?->id,
            'action' => $action,
            'entite' => class_basename($modele),
            'entite_id' => $modele->getKey(),
            'details' => $details,
        ]);
    }

    public function tracerChamps(Model $modele, array $avant, array $apres): void
    {
        $changes = [];
        foreach ($apres as $champ => $valeur) {
            if (($avant[$champ] ?? null) != $valeur) {
                $changes[$champ] = ['avant' => $avant[$champ] ?? null, 'apres' => $valeur];
            }
        }

        if (! empty($changes)) {
            $this->tracer('updated', $modele, $changes);
        }
    }
}