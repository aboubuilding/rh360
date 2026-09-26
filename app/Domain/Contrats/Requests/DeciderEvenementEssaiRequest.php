<?php

namespace App\Domain\Contrats\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeciderEvenementEssaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.validate');
    }

    public function rules(): array
    {
        return [
            'note_decision' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note_decision.required' => 'La note de décision est obligatoire.',
            'note_decision.min' => 'La note doit contenir au moins 5 caractères.',
            'note_decision.max' => 'La note ne doit pas dépasser 2000 caractères.',
        ];
    }
}