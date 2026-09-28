<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\FamilleRisque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRisqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('risks.manage');
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:500'],
            'famille' => ['required', Rule::in(array_column(FamilleRisque::cases(), 'value'))],
            'site' => ['required', 'string', 'max:255'],
            'poste_id' => ['nullable', 'exists:postes,id'],
            'activite' => ['required', 'string', 'max:300'],
            'danger' => ['required', 'string', 'max:3000'],
            'consequences' => ['required', 'string', 'max:3000'],
            'date_identification' => ['nullable', 'date'],
            'responsable_salarie_id' => ['required', 'exists:salaries,id'],
            'date_echeance_revue' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'famille.required' => 'La famille de risque est obligatoire.',
            'famille.in' => 'La famille sélectionnée est invalide.',
            'site.required' => 'Le site est obligatoire.',
            'activite.required' => 'L\'activité est obligatoire.',
            'danger.required' => 'La description du danger est obligatoire.',
            'consequences.required' => 'Les conséquences sont obligatoires.',
            'responsable_salarie_id.required' => 'Le responsable est obligatoire.',
            'responsable_salarie_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'date_echeance_revue.date' => 'La date de revue doit être une date valide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'intitule' => 'intitulé',
            'famille' => 'famille',
            'site' => 'site',
            'poste_id' => 'poste',
            'activite' => 'activité',
            'danger' => 'danger',
            'consequences' => 'conséquences',
            'responsable_salarie_id' => 'responsable',
        ];
    }
}