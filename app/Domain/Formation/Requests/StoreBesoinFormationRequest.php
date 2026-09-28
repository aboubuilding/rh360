<?php

namespace App\Domain\Formation\Requests;

use App\Domain\Formation\Enums\PrioriteBesoinFormation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBesoinFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['nullable', 'exists:salaries,id'],
            'intitule' => ['required', 'string', 'max:255'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'priorite' => ['required', Rule::in(array_column(PrioriteBesoinFormation::cases(), 'value'))],
            'annee_cible' => ['required', 'integer', 'min:' . (now()->year - 1), 'max:' . (now()->year + 5)],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'intitule.required' => 'L\'intitulé du besoin est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 255 caractères.',
            'priorite.required' => 'La priorité est obligatoire.',
            'priorite.in' => 'La priorité sélectionnée est invalide.',
            'annee_cible.required' => 'L\'année cible est obligatoire.',
            'annee_cible.integer' => 'L\'année doit être un entier.',
            'annee_cible.min' => 'L\'année ne peut pas être antérieure à ' . (now()->year - 1) . '.',
            'annee_cible.max' => 'L\'année ne peut pas dépasser ' . (now()->year + 5) . '.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'intitule' => 'intitulé',
            'motif' => 'motif',
            'priorite' => 'priorité',
            'annee_cible' => 'année cible',
        ];
    }
}