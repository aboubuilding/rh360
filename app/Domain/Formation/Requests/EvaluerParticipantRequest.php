<?php

namespace App\Domain\Formation\Requests;

use App\Domain\Formation\Enums\StatutPresenceParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluerParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'statut_presence' => ['required', Rule::in(array_column(StatutPresenceParticipant::cases(), 'value'))],
            'score_avant' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'score_apres' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note_satisfaction' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'commentaire_evaluation' => ['nullable', 'string', 'max:2000'],
            'reference_attestation' => ['nullable', 'string', 'max:240'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut_presence.required' => 'Le statut de présence est obligatoire.',
            'statut_presence.in' => 'Le statut sélectionné est invalide.',
            'score_avant.numeric' => 'Le score avant doit être un nombre.',
            'score_avant.max' => 'Le score avant ne peut pas dépasser 100.',
            'score_apres.numeric' => 'Le score après doit être un nombre.',
            'score_apres.max' => 'Le score après ne peut pas dépasser 100.',
            'note_satisfaction.numeric' => 'La note de satisfaction doit être un nombre.',
            'note_satisfaction.max' => 'La note de satisfaction ne peut pas dépasser 5.',
            'commentaire_evaluation.max' => 'Le commentaire ne doit pas dépasser 2000 caractères.',
        ];
    }
}