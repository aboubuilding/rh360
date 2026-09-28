<?php

namespace App\Domain\Conges\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransmettreAbsencesPaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.manage');
    }

    public function rules(): array
    {
        return [
            'absence_ids' => ['required', 'array', 'min:1'],
            'absence_ids.*' => ['integer', 'exists:absences,id'],
            'periode_paie' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'absence_ids.required' => 'Sélectionnez au moins une absence.',
            'absence_ids.array' => 'La sélection est invalide.',
            'absence_ids.min' => 'Sélectionnez au moins une absence.',
            'absence_ids.*.exists' => 'Une absence sélectionnée n\'existe pas.',
            'periode_paie.required' => 'La période de paie est obligatoire.',
            'periode_paie.regex' => 'La période doit être au format YYYY-MM.',
        ];
    }

    public function attributes(): array
    {
        return [
            'absence_ids' => 'absences',
            'periode_paie' => 'période de paie',
        ];
    }
}