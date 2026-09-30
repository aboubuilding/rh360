<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferentielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $referentielId = $this->route('referentiel')?->id;

        return [
            'code' => [
                'required', 'string', 'max:160',
                Rule::unique('referentiels_classification', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($referentielId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'type_referentiel' => ['required', 'string', 'max:60'],
            'niveau_source' => ['required', 'string', 'max:80'],
            'intitule_source' => ['nullable', 'string', 'max:255'],
            'reference_source' => ['nullable', 'string', 'max:255'],
            'portee' => ['nullable', 'string'],
            'priorite' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'debut_effet' => ['nullable', 'date'],
            'fin_effet' => ['nullable', 'date', 'after_or_equal:debut_effet'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code du référentiel est obligatoire.',
            'code.unique' => 'Ce code de référentiel est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 160 caractères.',
            'nom.required' => 'Le nom du référentiel est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'type_referentiel.required' => 'Le type de référentiel est obligatoire.',
            'type_referentiel.max' => 'Le type ne doit pas dépasser 60 caractères.',
            'niveau_source.required' => 'Le niveau de la source est obligatoire.',
            'niveau_source.max' => 'Le niveau de la source ne doit pas dépasser 80 caractères.',
            'priorite.integer' => 'La priorité doit être un nombre entier.',
            'priorite.min' => 'La priorité ne peut pas être négative.',
            'priorite.max' => 'La priorité ne peut pas dépasser 9999.',
            'debut_effet.date' => 'La date de début d\'effet doit être valide.',
            'fin_effet.date' => 'La date de fin d\'effet doit être valide.',
            'fin_effet.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code',
            'nom' => 'nom',
            'type_referentiel' => 'type de référentiel',
            'niveau_source' => 'niveau de la source',
            'intitule_source' => 'intitulé de la source',
            'reference_source' => 'référence de la source',
            'portee' => 'portée',
            'priorite' => 'priorité',
            'debut_effet' => 'date de début d\'effet',
            'fin_effet' => 'date de fin d\'effet',
        ];
    }
}