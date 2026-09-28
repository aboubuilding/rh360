<?php

namespace App\Console\Commands;

use App\Domain\Sst\Enums\StatutHabilitation;
use App\Domain\Sst\Events\HabilitationExpireBientot;
use App\Domain\Sst\Models\Habilitation;
use Illuminate\Console\Command;

class MarquerHabilitationsExpirees extends Command
{
    protected $signature = 'rh:marquer-habilitations-expirees {--entreprise=}';
    protected $description = 'Marque comme expirées les habilitations dont la date de fin est dépassée';

    public function handle(): int
    {
        $entrepriseId = $this->option('entreprise');

        $query = Habilitation::withoutGlobalScopes()
            ->where('statut', StatutHabilitation::ACTIVE->value)
            ->whereNotNull('date_fin')
            ->where('date_fin', '<', now());

        if ($entrepriseId) {
            $query->where('entreprise_id', $entrepriseId);
        }

        $habilitations = $query->get();
        $compteur = 0;

        foreach ($habilitations as $habilitation) {
            $habilitation->update([
                'statut' => StatutHabilitation::EXPIREE->value,
                'revision' => $habilitation->revision + 1,
            ]);
            $compteur++;
        }

        $this->info("{$compteur} habilitation(s) marquée(s) comme expirée(s).");

        // Émettre un événement pour les habilitations qui expirent dans 30 jours
        $bientot = Habilitation::withoutGlobalScopes()
            ->where('statut', StatutHabilitation::ACTIVE->value)
            ->whereBetween('date_fin', [now(), now()->addDays(30)])
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->get();

        foreach ($bientot as $h) {
            event(new HabilitationExpireBientot($h));
        }

        $this->info("{$bientot->count()} habilitation(s) expirant sous 30 jours notifiée(s).");

        return self::SUCCESS;
    }
}