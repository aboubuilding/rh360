<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'type_conge_id' => ['required', 'exists:types_conges,id'],
            'date_demande' => ['nullable', 'date'],
            'date_debut' => ['required', 'date'],
            'date_reprise' => ['nullable', 'date', 'after:date_debut'],
            'duree_jours' => ['nullable', 'numeric', 'min:0'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'remplacant' => ['nullable', 'string', 'max:255'],
            'reference_acte' => ['nullable', 'string', 'max:255'],
            'date_acte' => ['nullable', 'date'],
            // L'événement appartient au type de demande choisi.
            'evenement_permission_id' => ['nullable', Rule::exists('evenements_permission', 'id')->where('type_conge_id', $this->input('type_conge_id'))],
            'date_evenement' => ['nullable', 'date', 'required_with:evenement_permission_id'],
            'force_majeure' => ['boolean'],
            // Départ demandé par le salarié, ou fixé par l'employeur (ordre de départ à communiquer à l'avance).
            'initiative' => ['nullable', Rule::in(['salarie', 'employeur'])],
            'duree_heures' => ['nullable', 'numeric', 'min:0.25', 'max:1000'],
            // L'enfant appartient au foyer du salarié de la demande.
            'membre_foyer_id' => ['nullable', Rule::exists('membres_foyer', 'id')->where('salarie_id', $this->input('salarie_id', $this->route('demande')?->salarie_id))],
            'motif_force_majeure' => ['nullable', 'required_if:force_majeure,1', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'type_conge_id.required' => 'Le type de congé est obligatoire.',
            'type_conge_id.exists' => 'Le type de congé sélectionné n\'existe pas.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_reprise.date' => 'La date de reprise doit être une date valide.',
            'date_reprise.after' => 'La date de reprise doit être postérieure à la date de début.',
            'duree_jours.numeric' => 'La durée doit être un nombre.',
            'duree_jours.min' => 'La durée ne peut pas être négative.',
            'motif.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
            'date_evenement.required_with' => 'La date de l\'événement est obligatoire.',
            'motif_force_majeure.required_if' => 'Précisez le cas de force majeure.',
            'membre_foyer_id.exists' => 'Cette personne ne fait pas partie du foyer du salarié.',
            'duree_heures.min' => 'La durée en heures doit être d\'au moins un quart d\'heure.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'type_conge_id' => 'type de congé',
            'date_debut' => 'date de début',
            'date_reprise' => 'date de reprise',
            'duree_jours' => 'durée',
            'motif' => 'motif',
            'remplacant' => 'remplaçant',
            'reference_acte' => 'référence de l\'acte',
            'date_acte' => 'date de l\'acte',
        ];
    }
}