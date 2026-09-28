<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsenceRequest extends FormRequest
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
            'debut_le' => ['required', 'date'],
            'fin_le' => ['nullable', 'date', 'after_or_equal:debut_le'],
            'duree_heures' => ['nullable', 'numeric', 'min:0'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'justification' => ['nullable', 'string', 'max:4000'],
            'date_limite_justification' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'type_conge_id.required' => 'Le type est obligatoire.',
            'type_conge_id.exists' => 'Le type sélectionné n\'existe pas.',
            'debut_le.required' => 'La date de début est obligatoire.',
            'debut_le.date' => 'La date de début doit être une date valide.',
            'fin_le.date' => 'La date de fin doit être une date valide.',
            'fin_le.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'duree_heures.numeric' => 'La durée doit être un nombre.',
            'duree_heures.min' => 'La durée ne peut pas être négative.',
            'motif.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'type_conge_id' => 'type',
            'debut_le' => 'date de début',
            'fin_le' => 'date de fin',
            'duree_heures' => 'durée en heures',
            'motif' => 'motif',
            'justification' => 'justification',
            'date_limite_justification' => 'date limite de justification',
        ];
    }
}