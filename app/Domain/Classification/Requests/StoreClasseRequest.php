<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $classeId = $this->route('classe')?->id;

        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('classes_classification', 'code')
                    ->where('referentiel_id', $this->input('referentiel_id'))
                    ->ignore($classeId),
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
        ];
    }
}