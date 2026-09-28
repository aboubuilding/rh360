<?php

namespace App\Domain\Carriere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoumettreMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'date_eligibilite' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_eligibilite.date' => 'La date d\'éligibilité doit être une date valide.',
        ];
    }
}