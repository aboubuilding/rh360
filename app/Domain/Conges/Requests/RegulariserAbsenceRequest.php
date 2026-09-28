<?php

namespace App\Domain\Conges\Requests;

use App\Domain\Conges\Enums\QualificationAbsence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegulariserAbsenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.validate');
    }

    public function rules(): array
    {
        return [
            'qualification' => ['required', Rule::in(array_column(QualificationAbsence::cases(), 'value'))],
            'decision' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'qualification.required' => 'La qualification est obligatoire.',
            'qualification.in' => 'La qualification sélectionnée est invalide.',
            'decision.required' => 'La décision motivée est obligatoire.',
            'decision.min' => 'La décision doit contenir au moins 5 caractères.',
            'decision.max' => 'La décision ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'qualification' => 'qualification',
            'decision' => 'décision motivée',
        ];
    }
}