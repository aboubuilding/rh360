<?php

namespace App\Domain\Carriere\Requests;

use App\Domain\Carriere\Enums\StatutFiabilite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReprendreSituationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'position_classification_id' => ['required', 'exists:positions_classification,id'],
            'position_ouverture_id' => ['nullable', 'exists:positions_classification,id'],
            'date_reference_ouverture' => ['nullable', 'date'],
            'date_effet_categorie' => ['nullable', 'date'],
            'date_effet_classe' => ['nullable', 'date'],
            'date_effet_echelon' => ['nullable', 'date'],
            'date_reference_avancement' => ['nullable', 'date'],
            'reference_acte' => ['nullable', 'string', 'max:255'],
            'date_acte' => ['nullable', 'date'],
            'chemin_justificatif' => ['nullable', 'string', 'max:500'],
            'observations' => ['nullable', 'string', 'max:4000'],
            'statut_fiabilite' => ['nullable', Rule::in(array_column(StatutFiabilite::cases(), 'value'))],
            'justificatif' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'position_classification_id.required' => 'La position de classification est obligatoire.',
            'position_classification_id.exists' => 'La position sélectionnée n\'existe pas.',
            'position_ouverture_id.exists' => 'La position d\'ouverture n\'existe pas.',
            'date_reference_ouverture.date' => 'La date de référence d\'ouverture doit être une date valide.',
            'date_effet_categorie.date' => 'La date d\'effet de la catégorie doit être une date valide.',
            'date_effet_classe.date' => 'La date d\'effet de la classe doit être une date valide.',
            'date_effet_echelon.date' => 'La date d\'effet de l\'échelon doit être une date valide.',
            'date_reference_avancement.date' => 'La date de référence d\'avancement doit être une date valide.',
            'justificatif.mimes' => 'Le justificatif doit être au format PDF, PNG, JPG ou JPEG.',
            'justificatif.max' => 'Le justificatif ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'position_classification_id' => 'position de classification',
            'position_ouverture_id' => 'position d\'ouverture',
            'date_reference_ouverture' => 'date de référence d\'ouverture',
            'date_effet_categorie' => 'date d\'effet de la catégorie',
            'date_effet_classe' => 'date d\'effet de la classe',
            'date_effet_echelon' => 'date d\'effet de l\'échelon',
            'date_reference_avancement' => 'date de référence d\'avancement',
            'reference_acte' => 'référence de l\'acte',
            'date_acte' => 'date de l\'acte',
            'justificatif' => 'justificatif',
        ];
    }
}