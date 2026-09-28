<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegleIrppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        return [
            'debut_effet' => ['required', 'date'],
            'fin_effet' => ['nullable', 'date', 'after:debut_effet'],
            'reference_legale' => ['nullable', 'string', 'max:255'],
            'taux_abattement_professionnel' => ['required', 'numeric', 'min:0', 'max:100'],
            'plafond_abattement_professionnel' => ['required', 'numeric', 'min:0'],
            'deduction_mensuelle_par_charge' => ['required', 'numeric', 'min:0'],
            'nombre_max_charges' => ['required', 'integer', 'min:0', 'max:20'],
            'tranches' => ['required', 'array', 'min:1'],
            'tranches.*' => ['numeric', 'min:0'],
            'taux_tranches' => ['required', 'array', 'min:1'],
            'taux_tranches.*' => ['numeric', 'min:0', 'max:100'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'debut_effet.required' => 'La date de début d\'effet est obligatoire.',
            'fin_effet.after' => 'La date de fin doit être postérieure à la date de début.',
            'taux_abattement_professionnel.required' => 'Le taux d\'abattement est obligatoire.',
            'taux_abattement_professionnel.max' => 'Le taux d\'abattement ne peut pas dépasser 100.',
            'plafond_abattement_professionnel.required' => 'Le plafond d\'abattement est obligatoire.',
            'deduction_mensuelle_par_charge.required' => 'La déduction mensuelle par charge est obligatoire.',
            'nombre_max_charges.required' => 'Le nombre maximum de charges est obligatoire.',
            'nombre_max_charges.max' => 'Le nombre maximum de charges ne peut pas dépasser 20.',
            'tranches.required' => 'Les tranches sont obligatoires.',
            'tranches.array' => 'Les tranches doivent être une liste.',
            'tranches.min' => 'Au moins une tranche est requise.',
            'tranches.*.numeric' => 'Chaque tranche doit être un nombre.',
            'taux_tranches.required' => 'Les taux des tranches sont obligatoires.',
            'taux_tranches.min' => 'Au moins un taux de tranche est requis.',
            'taux_tranches.*.max' => 'Chaque taux ne peut pas dépasser 100.',
        ];
    }
}