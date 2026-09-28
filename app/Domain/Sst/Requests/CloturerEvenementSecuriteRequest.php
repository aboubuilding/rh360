<?php

namespace App\Domain\Sst\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloturerEvenementSecuriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('safety.manage');
    }

    public function rules(): array
    {
        return [
            'synthese_cloture' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'synthese_cloture.required' => 'La synthèse de clôture est obligatoire.',
            'synthese_cloture.min' => 'La synthèse doit contenir au moins 10 caractères.',
            'synthese_cloture.max' => 'La synthèse ne doit pas dépasser 5000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return ['synthese_cloture' => 'synthèse de clôture'];
    }
}