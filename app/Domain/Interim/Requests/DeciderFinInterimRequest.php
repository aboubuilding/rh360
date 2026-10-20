<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\DecisionFinInterim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeciderFinInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $reclassement = Rule::requiredIf($this->input('decision') === DecisionFinInterim::RECLASSEMENT->value);

        return [
            'decision' => ['required', Rule::enum(DecisionFinInterim::class)],
            'date_effet' => ['required', 'date'],
            'position_cible_id' => [$reclassement, 'nullable', 'exists:positions_classification,id'],
            'nouveau_salaire_base' => [$reclassement, 'nullable', 'integer', 'min:1'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
            'piece' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La décision est obligatoire.',
            'decision.enum' => 'La décision sélectionnée est invalide.',
            'date_effet.required' => 'La date d\'effet est obligatoire.',
            'position_cible_id.required' => 'La nouvelle position est obligatoire pour un reclassement.',
            'nouveau_salaire_base.required' => 'Le nouveau salaire de base est obligatoire pour un reclassement.',
            'nouveau_salaire_base.integer' => 'Le salaire de base est un montant entier en FCFA.',
            'piece.required' => 'La pièce justificative de la décision est obligatoire.',
            'piece.mimes' => 'La pièce doit être un fichier PDF, PNG ou JPEG.',
            'piece.max' => 'La pièce ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'decision' => 'décision',
            'date_effet' => 'date d\'effet',
            'position_cible_id' => 'nouvelle position',
            'nouveau_salaire_base' => 'nouveau salaire de base',
            'commentaire' => 'commentaire',
            'piece' => 'pièce justificative',
        ];
    }
}
