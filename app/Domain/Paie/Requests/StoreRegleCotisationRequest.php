<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegleCotisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        $regleId = $this->route('regle')?->id;

        return [
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('regles_cotisations', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($regleId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'taux_salarial' => ['required', 'numeric', 'min:0', 'max:100'],
            'taux_patronal' => ['required', 'numeric', 'min:0', 'max:100'],
            'debut_effet' => ['required', 'date'],
            'fin_effet' => ['nullable', 'date', 'after:debut_effet'],
            'reference_legale' => ['nullable', 'string', 'max:255'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé dans votre entreprise.',
            'nom.required' => 'Le nom est obligatoire.',
            'taux_salarial.required' => 'Le taux salarial est obligatoire.',
            'taux_salarial.numeric' => 'Le taux salarial doit être un nombre.',
            'taux_salarial.min' => 'Le taux salarial ne peut pas être négatif.',
            'taux_salarial.max' => 'Le taux salarial ne peut pas dépasser 100.',
            'taux_patronal.required' => 'Le taux patronal est obligatoire.',
            'taux_patronal.numeric' => 'Le taux patronal doit être un nombre.',
            'taux_patronal.max' => 'Le taux patronal ne peut pas dépasser 100.',
            'debut_effet.required' => 'La date de début d\'effet est obligatoire.',
            'debut_effet.date' => 'La date de début doit être une date valide.',
            'fin_effet.date' => 'La date de fin doit être une date valide.',
            'fin_effet.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}