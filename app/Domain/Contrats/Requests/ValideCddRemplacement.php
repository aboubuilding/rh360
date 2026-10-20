<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\MotifRecoursCdd;
use App\Domain\Contrats\Enums\TermeEvenementCdd;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Services\ValidateurCddRemplacement;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * RG-REM-01 à RG-REM-05 — champs du CDD de remplacement et contrôles métier, partagés par
 * la création, la modification et l'avenant d'un contrat.
 */
trait ValideCddRemplacement
{
    /** @return array<string, mixed> */
    protected function reglesCdd(): array
    {
        return [
            'motif_recours' => ['nullable', Rule::enum(MotifRecoursCdd::class)],
            'titulaire_remplace_id' => ['nullable', Rule::exists('salaries', 'id')->where('entreprise_id', $this->user()->entreprise_id)],
            'terme_evenement' => ['nullable', Rule::enum(TermeEvenementCdd::class)],
            'journalier_paye_fin_journee' => ['boolean'],
            'mention_caractere_provisoire' => ['nullable', 'string', 'max:2000'],
        ];
    }

    // Contrat dont dépend le nouveau contrat dans la chaîne des renouvellements.
    abstract protected function contratParent(): ?Contrat;

    abstract protected function salarieConcerne(): ?int;

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $donnees = $validator->validated() + ['salarie_id' => $this->salarieConcerne()];
                foreach (app(ValidateurCddRemplacement::class)->controler($donnees, $this->contratParent()) as $champ => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($champ, $message);
                    }
                }
            },
        ];
    }
}
