<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirPeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        return [
            'annee' => ['required', 'integer', 'min:2000', 'max:' . (now()->year + 5)],
            'mois' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'annee.required' => 'L\'année est obligatoire.',
            'annee.integer' => 'L\'année doit être un nombre entier.',
            'annee.min' => 'L\'année ne peut pas être antérieure à 2000.',
            'annee.max' => 'L\'année ne peut pas dépasser ' . (now()->year + 5) . '.',
            'mois.required' => 'Le mois est obligatoire.',
            'mois.integer' => 'Le mois doit être un nombre entier.',
            'mois.min' => 'Le mois doit être compris entre 1 et 12.',
            'mois.max' => 'Le mois doit être compris entre 1 et 12.',
        ];
    }

    public function attributes(): array
    {
        return [
            'annee' => 'année',
            'mois' => 'mois',
        ];
    }
}