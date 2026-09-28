<?php

namespace App\Domain\Formation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjouterParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
        ];
    }
}