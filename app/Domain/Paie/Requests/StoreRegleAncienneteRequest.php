<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegleAncienneteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        return [
            'annees_min' => ['required', 'integer', 'min:0', 'max:50'],
            'taux_initial' => ['required', 'numeric', 'min:0', 'max:100'],
            'increment_annuel' => ['required', 'numeric', 'min:0', 'max:50'],
            'taux_max' => ['required', 'numeric', 'min:0', 'max:100', 'gte:taux_initial'],
            'mode_base' => ['required', 'string', 'max:60'],
            'debut_effet' => ['required', 'date'],
            'fin_effet' => ['nullable', 'date', 'after:debut_effet'],
            'reference_legale' => ['nullable', 'string', 'max:255'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'annees_min.required' => 'Le nombre d\'années minimum est obligatoire.',
            'annees_min.integer' => 'Le nombre d\'années doit être un entier.',
            'annees_min.min' => 'Le nombre d\'années ne peut pas être négatif.',
            'taux_initial.required' => 'Le taux initial est obligatoire.',
            'taux_initial.numeric' => 'Le taux initial doit être un nombre.',
            'taux_initial.max' => 'Le taux initial ne peut pas dépasser 100.',
            'increment_annuel.required' => 'L\'incrément annuel est obligatoire.',
            'increment_annuel.numeric' => 'L\'incrément annuel doit être un nombre.',
            'taux_max.required' => 'Le taux maximum est obligatoire.',
            'taux_max.gte' => 'Le taux maximum doit être supérieur ou égal au taux initial.',
            'mode_base.required' => 'Le mode de base est obligatoire.',
            'debut_effet.required' => 'La date de début d\'effet est obligatoire.',
            'fin_effet.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}