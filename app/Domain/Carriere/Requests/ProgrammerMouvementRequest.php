<?php

namespace App\Domain\Carriere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgrammerMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.validate');
    }

    public function rules(): array
    {
        return [
            'date_effet' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_effet.required' => 'La date d\'effet est obligatoire.',
            'date_effet.date' => 'La date d\'effet doit être une date valide.',
            'date_effet.after' => 'La date d\'effet doit être postérieure à aujourd\'hui.',
        ];
    }

    public function attributes(): array
    {
        return ['date_effet' => 'date d\'effet'];
    }
}