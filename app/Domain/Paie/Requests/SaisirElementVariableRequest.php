<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaisirElementVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'rubrique_id' => ['required', 'exists:rubriques_paie,id'],
            'quantite' => ['nullable', 'numeric'],
            'taux' => ['nullable', 'numeric'],
            'montant' => ['required', 'numeric'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'rubrique_id.required' => 'La rubrique est obligatoire.',
            'rubrique_id.exists' => 'La rubrique sélectionnée n\'existe pas.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'taux.numeric' => 'Le taux doit être un nombre.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'observations.max' => 'Les observations ne doivent pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'rubrique_id' => 'rubrique',
            'quantite' => 'quantité',
            'taux' => 'taux',
            'montant' => 'montant',
            'observations' => 'observations',
        ];
    }
}