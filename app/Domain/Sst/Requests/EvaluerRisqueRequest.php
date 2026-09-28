<?php

namespace App\Domain\Sst\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluerRisqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('risks.manage');
    }

    public function rules(): array
    {
        return [
            'date_evaluation' => ['nullable', 'date'],
            'motif' => ['nullable', Rule::in(['initial', 'periodique', 'post_incident', 'changement', 'revision'])],
            'gravite' => ['required', 'integer', 'min:1', 'max:5'],
            'probabilite' => ['required', 'integer', 'min:1', 'max:5'],
            'version_methode' => ['nullable', 'string', 'max:80'],
            'mesures_existantes' => ['required', 'string', 'max:3000'],
            'justification' => ['required', 'string', 'max:3000'],
            'date_prochaine_revue' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_evaluation.date' => 'La date d\'évaluation doit être une date valide.',
            'motif.in' => 'Le motif sélectionné est invalide.',
            'gravite.required' => 'La gravité est obligatoire.',
            'gravite.min' => 'La gravité doit être comprise entre 1 et 5.',
            'gravite.max' => 'La gravité doit être comprise entre 1 et 5.',
            'probabilite.required' => 'La probabilité est obligatoire.',
            'probabilite.min' => 'La probabilité doit être comprise entre 1 et 5.',
            'probabilite.max' => 'La probabilité doit être comprise entre 1 et 5.',
            'mesures_existantes.required' => 'Les mesures existantes sont obligatoires.',
            'justification.required' => 'La justification est obligatoire.',
            'date_prochaine_revue.date' => 'La date de prochaine revue doit être une date valide.',
            'date_prochaine_revue.after' => 'La date de prochaine revue doit être future.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_evaluation' => 'date d\'évaluation',
            'motif' => 'motif',
            'gravite' => 'gravité',
            'probabilite' => 'probabilité',
            'mesures_existantes' => 'mesures existantes',
            'justification' => 'justification',
            'date_prochaine_revue' => 'date de prochaine revue',
        ];
    }
}