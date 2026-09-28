<?php

namespace App\Domain\Sst\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenouvelerHabilitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('habilitations.manage');
    }

    public function rules(): array
    {
        return [
            'emetteur' => ['nullable', 'string', 'max:255'],
            'reference_decision' => ['nullable', 'string', 'max:240'],
            'reference_formation' => ['nullable', 'string', 'max:255'],
            'date_decision' => ['nullable', 'date'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'date_revue' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}