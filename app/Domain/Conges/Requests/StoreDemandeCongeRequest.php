<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

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