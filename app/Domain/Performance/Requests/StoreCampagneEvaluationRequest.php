<?php

namespace App\Domain\Performance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampagneEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('performance.manage');
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:255'],
            'annee' => ['required', 'integer', 'min:' . (now()->year - 1), 'max:' . (now()->year + 2)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'statut' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'annee.required' => 'L\'année est obligatoire.',
            'annee.integer' => 'L\'année doit être un entier.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}