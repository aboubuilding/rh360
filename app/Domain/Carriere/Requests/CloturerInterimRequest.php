<?php

namespace App\Domain\Carriere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloturerInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'date_fin_reelle' => ['required', 'date'],
            'motif_cloture' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin_reelle.required' => 'La date de fin réelle est obligatoire.',
            'date_fin_reelle.date' => 'La date de fin réelle doit être une date valide.',
            'motif_cloture.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_fin_reelle' => 'date de fin réelle',
            'motif_cloture' => 'motif de clôture',
        ];
    }
}