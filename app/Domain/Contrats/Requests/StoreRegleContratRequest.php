<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\TypeContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegleContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.validate');
    }

    public function rules(): array
    {
        $regleId = $this->route('regle')?->id;

        return [
            'type_contrat' => ['required', Rule::in(array_column(TypeContrat::cases(), 'value'))],
            'categorie_id' => ['required', 'exists:categories_classification,id'],
            'date_effet' => ['required', 'date'],
            'parametres' => ['required', 'array'],
            'parametres.duree_max_essai_jours' => ['nullable', 'integer', 'min:0', 'max:365'],
            'parametres.duree_max_essai_renouvellement_jours' => ['nullable', 'integer', 'min:0', 'max:365'],
            'parametres.nombre_renouvellements_max' => ['nullable', 'integer', 'min:0', 'max:5'],
            'parametres.plafond_remuneration' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_contrat.required' => 'Le type de contrat est obligatoire.',
            'type_contrat.in' => 'Le type de contrat sélectionné est invalide.',
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'date_effet.required' => 'La date d\'effet est obligatoire.',
            'date_effet.date' => 'La date d\'effet doit être une date valide.',
            'parametres.required' => 'Les paramètres sont obligatoires.',
            'parametres.duree_max_essai_jours.integer' => 'La durée maximale d\'essai doit être un nombre entier.',
            'parametres.duree_max_essai_jours.max' => 'La durée maximale d\'essai ne peut pas dépasser 365 jours.',
            'parametres.nombre_renouvellements_max.max' => 'Le nombre de renouvellements ne peut pas dépasser 5.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_contrat' => 'type de contrat',
            'categorie_id' => 'catégorie',
            'date_effet' => 'date d\'effet',
            'parametres' => 'paramètres',
        ];
    }
}