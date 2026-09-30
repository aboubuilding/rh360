<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $positionId = $this->route('position')?->id;

        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:240',
                Rule::unique('positions_classification', 'code')
                    ->where('referentiel_id', $this->input('referentiel_id'))
                    ->ignore($positionId),
            ],
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

    public function messages(): array
    {
        return [
            'referentiel_id.required' => 'Le référentiel est obligatoire.',
            'referentiel_id.exists' => 'Le référentiel sélectionné n\'existe pas.',
            'code.required' => 'Le code de la position est obligatoire.',
            'code.unique' => 'Ce code de position est déjà utilisé pour ce référentiel.',
            'code.max' => 'Le code ne doit pas dépasser 240 caractères.',
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'classe_id.exists' => 'La classe sélectionnée n\'existe pas.',
            'echelon_id.exists' => 'L\'échelon sélectionné n\'existe pas.',
            'montant_salaire.integer' => 'Le salaire doit être un nombre entier (FCFA).',
            'montant_salaire.min' => 'Le salaire ne peut pas être négatif.',
            'salaire_minimum.integer' => 'Le salaire minimum doit être un nombre entier.',
            'salaire_minimum.min' => 'Le salaire minimum ne peut pas être négatif.',
            'position_conformite_id.exists' => 'La position de conformité n\'existe pas.',
            'position_suivante_id.exists' => 'La position suivante n\'existe pas.',
            'debut_effet.date' => 'La date de début d\'effet doit être valide.',
            'fin_effet.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    public function attributes(): array
    {
        return [
            'referentiel_id' => 'référentiel',
            'code' => 'code',
            'categorie_id' => 'catégorie',
            'classe_id' => 'classe',
            'echelon_id' => 'échelon',
            'montant_salaire' => 'salaire',
            'salaire_minimum' => 'salaire minimum',
            'position_conformite_id' => 'position de conformité',
            'position_suivante_id' => 'position suivante',
            'ordre' => 'ordre',
            'debut_effet' => 'date de début d\'effet',
            'fin_effet' => 'date de fin d\'effet',
            'statut' => 'statut',
        ];
    }
}