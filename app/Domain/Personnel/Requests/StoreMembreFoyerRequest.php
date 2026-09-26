<?php

namespace App\Domain\Personnel\Requests;

use App\Domain\Personnel\Enums\LienParente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembreFoyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('salaries.manage');
    }

    public function rules(): array
    {
        return [
            'lien_parente' => ['required', Rule::in(array_column(LienParente::cases(), 'value'))],
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'est_enfant_declare' => ['boolean'],
            'est_a_charge' => ['boolean'],
            'date_debut_charge' => ['nullable', 'date'],
            'date_fin_charge' => ['nullable', 'date', 'after_or_equal:date_debut_charge'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'acte_naissance' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'lien_parente.required' => 'Le lien de parenté est obligatoire.',
            'lien_parente.in' => 'Le lien de parenté sélectionné est invalide.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'prenoms.required' => 'Le prénom est obligatoire.',
            'prenoms.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_fin_charge.after_or_equal' => 'La date de fin de charge doit être postérieure ou égale à la date de début.',
            'photo.image' => 'La photo doit être une image.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'acte_naissance.mimes' => 'L\'acte de naissance doit être un PDF, PNG ou JPG.',
            'acte_naissance.max' => 'L\'acte de naissance ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'lien_parente' => 'lien de parenté',
            'nom' => 'nom',
            'prenoms' => 'prénom',
            'date_naissance' => 'date de naissance',
            'lieu_naissance' => 'lieu de naissance',
            'date_debut_charge' => 'date de début de charge',
            'date_fin_charge' => 'date de fin de charge',
            'photo' => 'photo',
            'acte_naissance' => 'acte de naissance',
        ];
    }
}