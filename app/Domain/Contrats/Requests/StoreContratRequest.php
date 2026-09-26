<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\TypeContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'reference' => ['required', 'string', 'max:240'],
            'type_contrat' => ['required', Rule::in(array_column(TypeContrat::cases(), 'value'))],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'poste_id' => ['required', 'exists:postes,id'],
            'position_classification_id' => ['required', 'exists:positions_classification,id'],
            'conditions' => ['nullable', 'array'],
            'regle_figee' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',

            'reference.required' => 'La référence du contrat est obligatoire.',
            'reference.max' => 'La référence ne doit pas dépasser 240 caractères.',

            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'type_contrat.in' => 'Le type de contrat sélectionné est invalide.',

            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',

            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',

            'poste_id.required' => 'Le poste est obligatoire.',
            'poste_id.exists' => 'Le poste sélectionné n\'existe pas.',

            'position_classification_id.required' => 'La position de classification est obligatoire.',
            'position_classification_id.exists' => 'La position sélectionnée n\'existe pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'reference' => 'référence',
            'type_contrat' => 'type de contrat',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'poste_id' => 'poste',
            'position_classification_id' => 'position de classification',
            'conditions' => 'conditions',
            'regle_figee' => 'règle figée',
        ];
    }
}