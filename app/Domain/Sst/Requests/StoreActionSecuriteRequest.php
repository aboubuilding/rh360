<?php

namespace App\Domain\Sst\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActionSecuriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('safety.manage');
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:500'],
            'responsable_salarie_id' => ['required', 'exists:salaries,id'],
            'date_echeance' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule.required' => 'L\'intitulé de l\'action est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 500 caractères.',
            'responsable_salarie_id.required' => 'Le responsable est obligatoire.',
            'responsable_salarie_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'date_echeance.required' => 'La date d\'échéance est obligatoire.',
            'date_echeance.date' => 'La date d\'échéance doit être une date valide.',
            'date_echeance.after_or_equal' => 'La date d\'échéance doit être aujourd\'hui ou dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return [
            'intitule' => 'intitulé',
            'responsable_salarie_id' => 'responsable',
            'date_echeance' => 'date d\'échéance',
        ];
    }
}