<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEchelonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $echelonId = $this->route('echelon')?->id;

        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('echelons_classification', 'code')
                    ->where('referentiel_id', $this->input('referentiel_id'))
                    ->ignore($echelonId),
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
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères.',
        ];
    }
}