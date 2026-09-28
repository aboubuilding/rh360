<?php

namespace App\Domain\Recrutement\Requests;

use App\Domain\Recrutement\Enums\SourceCandidat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('recrutement.manage');
    }

    public function rules(): array
    {
        return [
            'besoin_id' => ['nullable', 'exists:besoins_recrutement,id'],
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:120'],
            'source' => ['required', Rule::in(array_column(SourceCandidat::cases(), 'value'))],
            'observations' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'besoin_id.exists' => 'Le besoin sélectionné n\'existe pas.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'prenoms.required' => 'Le prénom est obligatoire.',
            'prenoms.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'source.required' => 'La source est obligatoire.',
            'source.in' => 'La source sélectionnée est invalide.',
        ];
    }
}