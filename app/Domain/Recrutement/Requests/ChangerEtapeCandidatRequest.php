<?php

namespace App\Domain\Recrutement\Requests;

use App\Domain\Recrutement\Enums\EtapeCandidat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangerEtapeCandidatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('recrutement.manage');
    }

    public function rules(): array
    {
        return [
            'etape' => ['required', Rule::in(array_column(EtapeCandidat::cases(), 'value'))],
            'observations' => ['nullable', 'string', 'max:2000'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'date_entretien' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'etape.required' => 'L\'étape est obligatoire.',
            'etape.in' => 'L\'étape sélectionnée est invalide.',
            'score.numeric' => 'Le score doit être un nombre.',
            'score.max' => 'Le score ne peut pas dépasser 100.',
            'date_entretien.date' => 'La date d\'entretien doit être une date valide.',
        ];
    }
}