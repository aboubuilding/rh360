<?php

namespace App\Domain\Interim\Requests;

use Illuminate\Foundation\Http\FormRequest;

// RG-INT-07 — avantage saisi à la main : accord entre les parties et justificatif obligatoires.
class StoreAvantageInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'rubrique_id' => ['required', 'exists:rubriques_paie,id'],
            'montant_mensuel' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'reference_accord' => ['required', 'string', 'max:240'],
            'justificatif' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'rubrique_id.required' => 'La rubrique de paie est obligatoire.',
            'montant_mensuel.required' => 'Le montant mensuel est obligatoire.',
            'montant_mensuel.integer' => 'Le montant est un entier en FCFA.',
            'reference_accord.required' => 'La référence de l\'accord entre les parties est obligatoire (CCIT, art. 13).',
            'justificatif.required' => 'Le justificatif de l\'accord est obligatoire (CCIT, art. 13).',
            'justificatif.mimes' => 'Le justificatif doit être un fichier PDF, PNG ou JPEG.',
            'justificatif.max' => 'Le justificatif ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rubrique_id' => 'rubrique',
            'montant_mensuel' => 'montant mensuel',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'reference_accord' => 'référence de l\'accord',
            'justificatif' => 'justificatif',
        ];
    }
}
