<?php

namespace App\Domain\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('organisation.manage');
    }

    public function rules(): array
    {
        $structureId = $this->route('structure')?->id;

        return [
            'type_structure_id' => ['required', 'exists:types_structures,id'],
            'parent_id' => [
                'nullable', 'exists:structures,id',
                Rule::notIn([$structureId]),
            ],
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('structures', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($structureId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'centre_cout' => ['nullable', 'string', 'max:200'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_structure_id.required' => 'Le type de structure est obligatoire.',
            'type_structure_id.exists' => 'Le type de structure sélectionné n\'existe pas.',
            'parent_id.exists' => 'La structure parente sélectionnée n\'existe pas.',
            'parent_id.not_in' => 'Une structure ne peut pas être sa propre structure parente.',
            'code.required' => 'Le code de la structure est obligatoire.',
            'code.unique' => 'Ce code de structure est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 100 caractères.',
            'nom.required' => 'Le nom de la structure est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'localisation.max' => 'La localisation ne doit pas dépasser 255 caractères.',
            'centre_cout.max' => 'Le centre de coût ne doit pas dépasser 200 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_structure_id' => 'type de structure',
            'parent_id' => 'structure parente',
            'code' => 'code',
            'nom' => 'nom',
            'localisation' => 'localisation',
            'centre_cout' => 'centre de coût',
        ];
    }
}