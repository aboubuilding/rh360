<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AffecterElementFixeRequest extends FormRequest
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
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
        ];
    }
}