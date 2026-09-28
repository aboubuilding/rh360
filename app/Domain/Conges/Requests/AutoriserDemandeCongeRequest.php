<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutoriserDemandeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.validate');
    }

    public function rules(): array
    {
        return [
            'reference_acte' => ['nullable', 'string', 'max:255'],
            'date_acte' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_acte.max' => 'La référence ne doit pas dépasser 255 caractères.',
            'date_acte.date' => 'La date de l\'acte doit être une date valide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reference_acte' => 'référence de l\'acte',
            'date_acte' => 'date de l\'acte',
        ];
    }
}