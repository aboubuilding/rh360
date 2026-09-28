<?php

namespace App\Domain\Carriere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProlongerInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'date_fin_prevue' => ['required', 'date', 'after:today'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin_prevue.required' => 'La nouvelle date de fin est obligatoire.',
            'date_fin_prevue.date' => 'La date de fin doit être une date valide.',
            'date_fin_prevue.after' => 'La date de fin doit être postérieure à aujourd\'hui.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_fin_prevue' => 'date de fin prévue',
            'motif' => 'motif',
        ];
    }
}