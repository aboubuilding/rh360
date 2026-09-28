<?php

namespace App\Domain\Sst\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHabilitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('habilitations.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'risque_id' => ['nullable', 'exists:risques,id'],
            'categorie' => ['required', 'string', 'max:60'],
            'intitule' => ['required', 'string', 'max:255'],
            'portee' => ['required', 'string', 'max:2000'],
            'emetteur' => ['nullable', 'string', 'max:255'],
            'reference_decision' => ['nullable', 'string', 'max:240'],
            'reference_formation' => ['nullable', 'string', 'max:255'],
            'date_decision' => ['nullable', 'date'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'date_revue' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'risque_id.exists' => 'Le risque sélectionné n\'existe pas.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'portee.required' => 'La portée est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'risque_id' => 'risque',
            'categorie' => 'catégorie',
            'intitule' => 'intitulé',
            'portee' => 'portée',
            'emetteur' => 'émetteur',
            'reference_decision' => 'référence de la décision',
            'reference_formation' => 'référence de la formation',
            'date_decision' => 'date de la décision',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'date_revue' => 'date de revue',
        ];
    }
}