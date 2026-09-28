<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\NatureOperationEpi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('ppe.manage');
    }

    public function rules(): array
    {
        return [
            'nature' => ['required', Rule::in(array_column(NatureOperationEpi::cases(), 'value'))],
            'date_evenement' => ['required', 'date'],
            'quantite' => ['nullable', 'integer', 'min:1'],
            'date_prochaine_verification' => ['nullable', 'date'],
            'intervenant' => ['nullable', 'string', 'max:255'],
            'resultat' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nature.required' => 'La nature de l\'opération est obligatoire.',
            'nature.in' => 'La nature sélectionnée est invalide.',
            'date_evenement.required' => 'La date de l\'opération est obligatoire.',
            'date_evenement.date' => 'La date doit être une date valide.',
            'quantite.integer' => 'La quantité doit être un entier.',
            'quantite.min' => 'La quantité doit être au moins de 1.',
            'date_prochaine_verification.date' => 'La date de prochaine vérification doit être une date valide.',
        ];
    }
}