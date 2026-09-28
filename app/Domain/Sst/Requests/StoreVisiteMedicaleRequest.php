<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\TypeVisiteMedicale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisiteMedicaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('health.manage')
            && $this->user()->peut('sensitive.social_health');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'type_visite' => ['required', Rule::in(array_column(TypeVisiteMedicale::cases(), 'value'))],
            'date_prevue' => ['required', 'date'],
            'prestataire' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'type_visite.required' => 'Le type de visite est obligatoire.',
            'type_visite.in' => 'Le type de visite sélectionné est invalide.',
            'date_prevue.required' => 'La date prévue est obligatoire.',
            'date_prevue.date' => 'La date prévue doit être une date valide.',
            'prestataire.max' => 'Le nom du prestataire ne doit pas dépasser 255 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'type_visite' => 'type de visite',
            'date_prevue' => 'date prévue',
            'prestataire' => 'prestataire',
        ];
    }
}