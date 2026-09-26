<?php

namespace App\Domain\Contrats\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValiderContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.validate');
    }

    public function rules(): array
    {
        return [
            'note_derogation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note_derogation.max' => 'La note de dérogation ne doit pas dépasser 2000 caractères.',
        ];
    }
}