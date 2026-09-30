<?php

namespace App\Domain\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('organisation.manage');
    }

    public function rules(): array
    {
        $typeId = $this->route('typeStructure')?->id;

        return [
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('types_structures', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($typeId),
            ],
            'nom' => ['required', 'string', 'max:240'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 100 caractères.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 240 caractères.',
            'ordre.integer' => 'L\'ordre doit être un nombre entier.',
            'ordre.min' => 'L\'ordre ne peut pas être négatif.',
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'code', 'nom' => 'nom', 'ordre' => 'ordre'];
    }
}