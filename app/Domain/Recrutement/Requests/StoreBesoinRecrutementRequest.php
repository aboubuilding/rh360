<?php

namespace App\Domain\Recrutement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBesoinRecrutementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('recrutement.manage');
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:100'],
            'intitule_poste' => ['required', 'string', 'max:255'],
            'departement' => ['nullable', 'string', 'max:255'],
            'nombre_postes' => ['required', 'integer', 'min:1', 'max:100'],
            'type_contrat' => ['required', 'string', 'max:60'],
            'date_cible' => ['nullable', 'date'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule_poste.required' => 'L\'intitulé du poste est obligatoire.',
            'nombre_postes.required' => 'Le nombre de postes est obligatoire.',
            'nombre_postes.integer' => 'Le nombre doit être un entier.',
            'nombre_postes.min' => 'Au moins 1 poste est requis.',
            'nombre_postes.max' => 'Le nombre ne peut pas dépasser 100.',
            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'date_cible.date' => 'La date cible doit être une date valide.',
        ];
    }
}