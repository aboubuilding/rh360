<?php

namespace App\Domain\Formation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'plan_formation_id' => ['nullable', 'exists:plan_formation,id'],
            'intitule' => ['required', 'string', 'max:255'],
            'prestataire' => ['nullable', 'string', 'max:255'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'duree_heures' => ['required', 'numeric', 'min:0'],
            'cout_reel' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_formation_id.exists' => 'Le plan de formation sélectionné n\'existe pas.',
            'intitule.required' => 'L\'intitulé de la session est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'duree_heures.required' => 'La durée est obligatoire.',
            'duree_heures.numeric' => 'La durée doit être un nombre.',
            'cout_reel.numeric' => 'Le coût réel doit être un nombre.',
            'cout_reel.min' => 'Le coût ne peut pas être négatif.',
        ];
    }
}