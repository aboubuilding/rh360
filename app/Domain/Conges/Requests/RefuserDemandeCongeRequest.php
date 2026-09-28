<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefuserDemandeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.validate');
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de refus est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
        ];
    }
}