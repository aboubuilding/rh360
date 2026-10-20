<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\StatutAgrement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// RG-TT-01 — agence d'intérim et agrément du ministre chargé du travail.
class StoreEntrepriseInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'raison_sociale' => ['required', 'string', 'max:255'],
            'numero_agrement' => [
                'nullable', 'string', 'max:120',
                Rule::unique('entreprises_interim', 'numero_agrement')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($this->route('agence')?->id),
            ],
            'date_agrement' => ['nullable', 'date', 'required_with:numero_agrement'],
            'date_fin_validite_agrement' => ['nullable', 'date', 'after_or_equal:date_agrement'],
            'statut_agrement' => ['required', Rule::enum(StatutAgrement::class)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'raison_sociale.required' => 'La raison sociale est obligatoire.',
            'numero_agrement.unique' => 'Ce numéro d\'agrément est déjà enregistré.',
            'date_agrement.required_with' => 'La date de l\'agrément est obligatoire avec son numéro.',
            'date_fin_validite_agrement.after_or_equal' => 'La fin de validité doit être postérieure à la date d\'agrément.',
            'statut_agrement.required' => 'Le statut de l\'agrément est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return [
            'raison_sociale' => 'raison sociale',
            'numero_agrement' => 'numéro d\'agrément',
            'date_agrement' => 'date d\'agrément',
            'date_fin_validite_agrement' => 'fin de validité de l\'agrément',
            'statut_agrement' => 'statut de l\'agrément',
        ];
    }
}
