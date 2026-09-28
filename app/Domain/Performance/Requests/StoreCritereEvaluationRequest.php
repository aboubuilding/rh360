<?php

namespace App\Domain\Performance\Requests;

use App\Domain\Performance\Enums\FamilleCritere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCritereEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('performance.manage');
    }

    public function rules(): array
    {
        $critereId = $this->route('critere')?->id;

        return [
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('criteres_evaluation', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($critereId),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'famille' => ['required', Rule::in(array_column(FamilleCritere::cases(), 'value'))],
            'ponderation' => ['required', 'numeric', 'min:0', 'max:10'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 80 caractères.',
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères.',
            'famille.required' => 'La famille est obligatoire.',
            'famille.in' => 'La famille sélectionnée est invalide.',
            'ponderation.required' => 'La pondération est obligatoire.',
            'ponderation.numeric' => 'La pondération doit être un nombre.',
            'ponderation.min' => 'La pondération ne peut pas être négative.',
            'ponderation.max' => 'La pondération ne peut pas dépasser 10.',
        ];
    }
}