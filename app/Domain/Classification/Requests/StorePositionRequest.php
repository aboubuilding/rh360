<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => ['required', 'string', 'max:240'],
            'categorie_id' => ['required', 'exists:categories_classification,id'],
            'classe_id' => ['nullable', 'exists:classes_classification,id'],
            'echelon_id' => ['nullable', 'exists:echelons_classification,id'],
            'montant_salaire' => ['nullable', 'integer', 'min:0'],
            'salaire_minimum' => ['nullable', 'integer', 'min:0'],
            'position_conformite_id' => ['nullable', 'exists:positions_classification,id'],
            'position_suivante_id' => ['nullable', 'exists:positions_classification,id'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'debut_effet' => ['nullable', 'date'],
            'fin_effet' => ['nullable', 'date', 'after_or_equal:debut_effet'],
            'actif' => ['boolean'],
            'statut' => ['nullable', 'string', 'max:50'],
        ];
    }
}