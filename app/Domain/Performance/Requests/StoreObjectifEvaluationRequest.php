<?php

namespace App\Domain\Performance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjectifEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('performance.manage');
    }

    public function rules(): array
    {
        return [
            'campagne_id' => ['required', 'exists:campagnes_evaluation,id'],
            'salarie_id' => ['required', 'exists:salaries,id'],
            'intitule' => ['required', 'string', 'max:255'],
            'indicateur' => ['nullable', 'string', 'max:255'],
            'cible' => ['nullable', 'string', 'max:255'],
            'ponderation' => ['required', 'numeric', 'min:0', 'max:10'],
            'date_echeance' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'campagne_id.required' => 'La campagne est obligatoire.',
            'campagne_id.exists' => 'La campagne sélectionnée n\'existe pas.',
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 255 caractères.',
            'ponderation.required' => 'La pondération est obligatoire.',
            'ponderation.numeric' => 'La pondération doit être un nombre.',
            'date_echeance.date' => 'La date d\'échéance doit être une date valide.',
        ];
    }
}