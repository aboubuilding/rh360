<?php

namespace App\Domain\Interim\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// RG-PMO-02 — contenu obligatoire de la convention et de l'avenant.
class StorePretMainOeuvreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', Rule::exists('salaries', 'id')->where('entreprise_id', $this->user()->entreprise_id)],
            'utilisatrice_raison_sociale' => ['required', 'string', 'max:255'],
            'utilisatrice_adresse' => ['nullable', 'string', 'max:255'],
            'utilisatrice_contact' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'qualification' => ['required', 'string', 'max:255'],
            'mode_calcul_refacturation' => ['required', 'string', 'max:2000'],
            'travail_confie' => ['required', 'string', 'max:2000'],
            'horaires' => ['required', 'string', 'max:255'],
            'lieu' => ['required', 'string', 'max:255'],
            'caracteristiques_poste' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié prêté est obligatoire.',
            'utilisatrice_raison_sociale.required' => 'L\'entreprise utilisatrice est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire (durée de la convention).',
            'date_fin.required' => 'La date de fin est obligatoire (durée de la convention).',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'qualification.required' => 'La qualification du salarié est obligatoire (convention).',
            'mode_calcul_refacturation.required' => 'Le mode de calcul de la refacturation est obligatoire (convention).',
            'travail_confie.required' => 'Le travail confié est obligatoire (avenant).',
            'horaires.required' => 'Les horaires sont obligatoires (avenant).',
            'lieu.required' => 'Le lieu d\'exécution est obligatoire (avenant).',
            'caracteristiques_poste.required' => 'Les caractéristiques du poste sont obligatoires (avenant).',
        ];
    }
}
