<?php

namespace App\Domain\Carriere\Requests;

use App\Domain\Carriere\Enums\TypeMouvement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'type_mouvement' => ['required', Rule::in(array_column(TypeMouvement::cases(), 'value'))],
            'sous_type_mouvement' => ['nullable', 'string', 'max:160'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'date_proposition' => ['nullable', 'date'],
            'date_eligibilite' => ['nullable', 'date'],
            'date_effet' => ['nullable', 'date'],

            // Structure / poste de départ
            'structure_depart_id' => ['nullable', 'exists:structures,id'],
            'poste_depart_id' => ['nullable', 'exists:postes,id'],
            'position_classification_depart_id' => ['nullable', 'exists:positions_classification,id'],
            'lieu_affectation_depart' => ['nullable', 'string', 'max:255'],

            // Structure / poste cible
            'structure_cible_id' => ['nullable', 'exists:structures,id'],
            'poste_cible_id' => ['nullable', 'exists:postes,id'],
            'position_classification_cible_id' => ['nullable', 'exists:positions_classification,id'],
            'lieu_affectation_cible' => ['nullable', 'string', 'max:255'],

            // Intérim
            'date_fin_prevue' => ['nullable', 'date', 'after:date_effet'],

            'reference_acte' => ['nullable', 'string', 'max:255'],
            'type_source' => ['nullable', 'string', 'max:120'],
            'reference_source' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'type_mouvement.required' => 'Le type de mouvement est obligatoire.',
            'type_mouvement.in' => 'Le type de mouvement sélectionné est invalide.',
            'sous_type_mouvement.max' => 'Le sous-type ne doit pas dépasser 160 caractères.',
            'motif.max' => 'Le motif ne doit pas dépasser 2000 caractères.',
            'date_proposition.date' => 'La date de proposition doit être une date valide.',
            'date_eligibilite.date' => 'La date d\'éligibilité doit être une date valide.',
            'date_effet.date' => 'La date d\'effet doit être une date valide.',
            'structure_depart_id.exists' => 'La structure de départ n\'existe pas.',
            'poste_depart_id.exists' => 'Le poste de départ n\'existe pas.',
            'position_classification_depart_id.exists' => 'La position de départ n\'existe pas.',
            'structure_cible_id.exists' => 'La structure cible n\'existe pas.',
            'poste_cible_id.exists' => 'Le poste cible n\'existe pas.',
            'position_classification_cible_id.exists' => 'La position cible n\'existe pas.',
            'date_fin_prevue.date' => 'La date de fin prévue doit être une date valide.',
            'date_fin_prevue.after' => 'La date de fin prévue doit être postérieure à la date d\'effet.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'type_mouvement' => 'type de mouvement',
            'sous_type_mouvement' => 'sous-type',
            'motif' => 'motif',
            'date_proposition' => 'date de proposition',
            'date_eligibilite' => 'date d\'éligibilité',
            'date_effet' => 'date d\'effet',
            'structure_depart_id' => 'structure de départ',
            'poste_depart_id' => 'poste de départ',
            'position_classification_depart_id' => 'position de départ',
            'structure_cible_id' => 'structure cible',
            'poste_cible_id' => 'poste cible',
            'position_classification_cible_id' => 'position cible',
            'date_fin_prevue' => 'date de fin prévue',
            'reference_acte' => 'référence de l\'acte',
        ];
    }
}