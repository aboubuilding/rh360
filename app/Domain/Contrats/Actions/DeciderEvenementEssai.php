<?php

namespace App\Domain\Contrats\Actions;

use App\Domain\Contrats\Enums\StatutEvenementEssai;
use App\Domain\Contrats\Events\EvenementEssaiValide;
use App\Domain\Contrats\Models\EvenementEssai;
use App\Domain\Contrats\Services\GenerateurAlertesContrats;
use Illuminate\Support\Facades\DB;

class DeciderEvenementEssai
{
    public function __construct(private GenerateurAlertesContrats $alertes) {}

    public function valider(EvenementEssai $evenement, ?string $note = null): EvenementEssai
    {
        return $this->decider($evenement, StatutEvenementEssai::VALIDE, $note);
    }

    public function refuser(EvenementEssai $evenement, string $note): EvenementEssai
    {
        return $this->decider($evenement, StatutEvenementEssai::REFUSE, $note);
    }

    private function decider(EvenementEssai $evenement, StatutEvenementEssai $statut, ?string $note): EvenementEssai
    {
        if (! $evenement->estEnAttente()) {
            throw new \DomainException('Cet événement a déjà été traité.');
        }

        return DB::transaction(function () use ($evenement, $statut, $note) {
            $evenement->update([
                'statut' => $statut->value,
                'decide_par' => auth()->id() ?? 1,
                'decide_le' => now(),
                'note_decision' => $note,
            ]);

            if ($statut === StatutEvenementEssai::VALIDE) {
                $this->alertes->resynchroniser($evenement->contrat);
                event(new EvenementEssaiValide($evenement));
            }

            return $evenement->fresh();
        });
    }
}