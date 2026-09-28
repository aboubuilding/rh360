<?php

namespace App\Domain\Formation\Requests;

use App\Domain\Formation\Enums\ModaliteFormation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        $formationId = $this->route('formation')?->id;

        return [
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('formations', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($formationId),
            ],
            'intitule' => ['required', 'string', 'max:255'],
            'domaine' => ['nullable', 'string', 'max:240'],
            'objectif' => ['nullable', 'string', 'max:3000'],
            'duree_heures' => ['required', 'numeric', 'min:0', 'max:1000'],
            'modalite' => ['required', Rule::in(array_column(ModaliteFormation::cases(), 'value'))],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 80 caractères.',
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 255 caractères.',
            'domaine.max' => 'Le domaine ne doit pas dépasser 240 caractères.',
            'objectif.max' => 'L\'objectif ne doit pas dépasser 3000 caractères.',
            'duree_heures.required' => 'La durée en heures est obligatoire.',
            'duree_heures.numeric' => 'La durée doit être un nombre.',
            'duree_heures.min' => 'La durée ne peut pas être négative.',
            'duree_heures.max' => 'La durée ne peut pas dépasser 1000 heures.',
            'modalite.required' => 'La modalité est obligatoire.',
            'modalite.in' => 'La modalité sélectionnée est invalide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code',
            'intitule' => 'intitulé',
            'domaine' => 'domaine',
            'objectif' => 'objectif',
            'duree_heures' => 'durée en heures',
            'modalite' => 'modalité',
        ];
    }
}