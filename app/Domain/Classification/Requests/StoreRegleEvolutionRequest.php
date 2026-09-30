<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegleEvolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        $regleId = $this->route('regleEvolution')?->id;

        return [
            'referentiel_id' => ['required', 'exists:referentiels_classification,id'],
            'code' => [
                'required', 'string', 'max:240',
                Rule::unique('regles_evolution', 'code')
                    ->where('referentiel_id', $this->input('referentiel_id'))
                    ->ignore($regleId),
            ],
            'type_evolution' => ['required', 'string', 'max:120'],
            'niveau_source' => ['required', 'string', 'max:80'],
            'intitule_source' => ['nullable', 'string', 'max:255'],
            'reference_source' => ['nullable', 'string', 'max:255'],
            'portee' => ['nullable', 'string'],
            'priorite' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'mois_min' => ['nullable', 'integer', 'min:0'],
            'mois_max' => ['nullable', 'integer', 'min:0', 'gte:mois_min'],
            'anticipation_autorisee' => ['boolean'],
            'condition_anticipation' => ['nullable', 'string'],
            'mode_reinitialisation_anticipation' => ['required', 'string', 'max:80'],
            'validation_requise' => ['boolean'],
            'regle_transitoire' => ['nullable', 'string'],
            'debut_effet' => ['nullable', 'date'],
            'fin_effet' => ['nullable', 'date', 'after_or_equal:debut_effet'],
            'actif' => ['boolean'],
            'statut' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'referentiel_id.required' => 'Le référentiel est obligatoire.',
            'referentiel_id.exists' => 'Le référentiel sélectionné n\'existe pas.',
            'code.required' => 'Le code de la règle est obligatoire.',
            'code.unique' => 'Ce code de règle est déjà utilisé pour ce référentiel.',
            'code.max' => 'Le code ne doit pas dépasser 240 caractères.',
            'type_evolution.required' => 'Le type d\'évolution est obligatoire.',
            'niveau_source.required' => 'Le niveau de la source est obligatoire.',
            'priorite.integer' => 'La priorité doit être un nombre entier.',
            'mois_min.integer' => 'Le délai minimum doit être un nombre entier.',
            'mois_max.gte' => 'Le délai maximum doit être supérieur ou égal au délai minimum.',
            'mode_reinitialisation_anticipation.required' => 'Le mode de réinitialisation est obligatoire.',
            'debut_effet.date' => 'La date de début d\'effet doit être valide.',
            'fin_effet.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}