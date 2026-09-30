<?php

namespace App\Domain\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('organisation.manage');
    }

    public function rules(): array
    {
        $posteId = $this->route('poste')?->id;

        return [
            'structure_id' => ['required', 'exists:structures,id'],
            'code' => [
                'required', 'string', 'max:100',
                Rule::unique('postes', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($posteId),
            ],
            'intitule' => ['required', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:200'],
            'effectif_cible' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'structure_id.required' => 'La structure de rattachement est obligatoire.',
            'structure_id.exists' => 'La structure sélectionnée n\'existe pas.',
            'code.required' => 'Le code du poste est obligatoire.',
            'code.unique' => 'Ce code de poste est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 100 caractères.',
            'intitule.required' => 'L\'intitulé du poste est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 255 caractères.',
            'categorie.max' => 'La catégorie ne doit pas dépasser 200 caractères.',
            'effectif_cible.integer' => 'L\'effectif cible doit être un nombre entier.',
            'effectif_cible.min' => 'L\'effectif cible ne peut pas être négatif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'structure_id' => 'structure',
            'code' => 'code',
            'intitule' => 'intitulé',
            'categorie' => 'catégorie',
            'effectif_cible' => 'effectif cible',
        ];
    }
}