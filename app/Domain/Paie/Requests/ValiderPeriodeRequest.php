<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValiderPeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.valider');
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.accepted' => 'Vous devez confirmer la validation définitive.',
        ];
    }
}