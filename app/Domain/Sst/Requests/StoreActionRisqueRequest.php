<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\TypeMesurePrevention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActionRisqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('risks.manage');
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:500'],
            'type_mesure' => ['required', Rule::in(array_column(TypeMesurePrevention::cases(), 'value'))],
            'responsable_salarie_id' => ['required', 'exists:salaries,id'],
            'date_echeance' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'intitule.required' => 'L\'intitulé de l\'action est obligatoire.',
            'type_mesure.required' => 'Le type de mesure est obligatoire.',
            'type_mesure.in' => 'Le type de mesure sélectionné est invalide.',
            'responsable_salarie_id.required' => 'Le responsable est obligatoire.',
            'responsable_salarie_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'date_echeance.required' => 'La date d\'échéance est obligatoire.',
            'date_echeance.after_or_equal' => 'La date d\'échéance doit être aujourd\'hui ou dans le futur.',
        ];
    }
}