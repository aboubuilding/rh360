<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\TypeContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.manage');
    }

    public function rules(): array
    {
        $entrepriseId = $this->user()->entreprise_id;

        return [
            'reference' => [
                'required', 'string', 'max:240',
                Rule::unique('contrats', 'reference')->where('entreprise_id', $entrepriseId),
            ],
            'type_contrat' => ['required', Rule::in(array_column(TypeContrat::cases(), 'value'))],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'poste_id' => ['required', Rule::exists('postes', 'id')->where('entreprise_id', $entrepriseId)],
            'position_classification_id' => ['required', 'exists:positions_classification,id'],
            'conditions' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.required' => 'La référence de l\'avenant est obligatoire.',
            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
            'poste_id.required' => 'Le poste est obligatoire.',
            'poste_id.exists' => 'Le poste sélectionné n\'existe pas.',
            'position_classification_id.required' => 'La position de classification est obligatoire.',
            'position_classification_id.exists' => 'La position sélectionnée n\'existe pas.',
        ];
    }
}