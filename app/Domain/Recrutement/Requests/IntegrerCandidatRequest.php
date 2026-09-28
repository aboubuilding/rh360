<?php

namespace App\Domain\Recrutement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IntegrerCandidatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('recrutement.manage');
    }

    public function rules(): array
    {
        return [
            'date_integration' => ['required', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_integration.required' => 'La date d\'intégration est obligatoire.',
            'date_integration.date' => 'La date d\'intégration doit être une date valide.',
        ];
    }
}