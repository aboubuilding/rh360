<?php

namespace App\Domain\Personnel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FusionnerSalariesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('salaries.fusion');
    }

    public function rules(): array
    {
        return [
            'cible_id' => ['required', 'exists:salaries,id', 'different:source_id'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cible_id.required' => 'La fiche cible est obligatoire.',
            'cible_id.exists' => 'La fiche cible n\'existe pas.',
            'cible_id.different' => 'La fiche cible doit être différente de la fiche source.',
            'motif.required' => 'Le motif de fusion est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return ['cible_id' => 'fiche cible', 'motif' => 'motif'];
    }
}