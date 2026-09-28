<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReouvrirPeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->estSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif de réouverture est obligatoire.',
            'motif.min' => 'Le motif doit contenir au moins 10 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
        ];
    }
}