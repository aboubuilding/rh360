<?php

namespace App\Domain\Carriere\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValiderMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.validate');
    }

    public function rules(): array
    {
        return [
            'note_validation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note_validation.max' => 'La note ne doit pas dépasser 2000 caractères.',
        ];
    }
}