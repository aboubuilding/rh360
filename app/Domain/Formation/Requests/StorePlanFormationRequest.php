<?php

namespace App\Domain\Formation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:255'],
            'annee' => ['required', 'integer', 'min:' . (now()->year - 1), 'max:' . (now()->year + 5)],
            'debut_prevu' => ['nullable', 'date'],
            'montant_budget' => ['required', 'numeric', 'min:0'],
            'statut' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule.required' => 'L\'intitulé du plan est obligatoire.',
            'annee.required' => 'L\'année est obligatoire.',
            'annee.integer' => 'L\'année doit être un entier.',
            'debut_prevu.date' => 'La date de début prévue doit être une date valide.',
            'montant_budget.required' => 'Le montant du budget est obligatoire.',
            'montant_budget.numeric' => 'Le budget doit être un nombre.',
            'montant_budget.min' => 'Le budget ne peut pas être négatif.',
        ];
    }
}