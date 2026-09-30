<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $categorieId = $this->route('category')?->id;

        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('categories_classification', 'code')
                    ->where('referentiel_id', $this->input('referentiel_id'))
                    ->ignore($categorieId),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'referentiel_id.required' => 'Le référentiel est obligatoire.',
            'referentiel_id.exists' => 'Le référentiel sélectionné n\'existe pas.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé pour ce référentiel.',
            'code.max' => 'Le code ne doit pas dépasser 160 caractères.',
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères.',
            'ordre.integer' => 'L\'ordre doit être un nombre entier.',
            'ordre.min' => 'L\'ordre ne peut pas être négatif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'referentiel_id' => 'référentiel',
            'code' => 'code',
            'libelle' => 'libellé',
            'ordre' => 'ordre',
        ];
    }
}