<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjusterSoldeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.soldes.manage');
    }

    public function rules(): array
    {
        return [
            'ajustement' => ['required', 'numeric', 'between:-365,365'],
            'motif' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ajustement.required' => 'L\'ajustement est obligatoire.',
            'ajustement.numeric' => 'L\'ajustement doit être un nombre.',
            'ajustement.between' => 'L\'ajustement doit être compris entre -365 et 365.',
            'motif.required' => 'Le motif est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ajustement' => 'ajustement',
            'motif' => 'motif',
        ];
    }
}