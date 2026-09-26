<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\NatureEvenementEssai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvenementEssaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.manage');
    }

    public function rules(): array
    {
        return [
            'nature' => ['required', Rule::in(array_column(NatureEvenementEssai::cases(), 'value'))],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'duree_jours' => ['nullable', 'integer', 'min:1', 'max:365'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nature.required' => 'La nature de l\'événement est obligatoire.',
            'nature.in' => 'La nature sélectionnée est invalide.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'duree_jours.integer' => 'La durée en jours doit être un nombre entier.',
            'duree_jours.min' => 'La durée doit être au moins de 1 jour.',
            'duree_jours.max' => 'La durée ne peut pas dépasser 365 jours.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nature' => 'nature',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
            'duree_jours' => 'durée en jours',
            'commentaire' => 'commentaire',
        ];
    }
}