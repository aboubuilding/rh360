<?php

namespace App\Domain\Conges\Requests;

use App\Domain\Conges\Enums\CategorieTypeConge;
use App\Domain\Conges\Enums\ImpactAnciennete;
use App\Domain\Conges\Enums\ImpactCongeAnnuel;
use App\Domain\Conges\Enums\TraitementSalarial;
use App\Domain\Conges\Enums\UniteConge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.manage');
    }

    public function rules(): array
    {
        $typeId = $this->route('type')?->id;

        return [
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('types_conges', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($typeId),
            ],
            'nom' => ['required', 'string', 'max:240'],
            'categorie' => ['required', Rule::in(array_column(CategorieTypeConge::cases(), 'value'))],
            'unite' => ['required', Rule::in(array_column(UniteConge::cases(), 'value'))],
            'droit_annuel' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'remunere' => ['boolean'],
            'justificatif_requis' => ['boolean'],
            'reference_legale' => ['nullable', 'string', 'max:240'],
            'duree_max' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'portee_duree_max' => ['nullable', 'string', 'max:60'],
            'traitement_salarial' => ['required', Rule::in(array_column(TraitementSalarial::cases(), 'value'))],
            'impact_conge_annuel' => ['required', Rule::in(array_column(ImpactCongeAnnuel::cases(), 'value'))],
            'impact_anciennete' => ['required', Rule::in(array_column(ImpactAnciennete::cases(), 'value'))],
            'delai_justification_jours' => ['nullable', 'integer', 'min:0', 'max:90'],
            'autorisation_prealable_requise' => ['boolean'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé dans votre entreprise.',
            'code.max' => 'Le code ne doit pas dépasser 80 caractères.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 240 caractères.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.in' => 'La catégorie sélectionnée est invalide.',
            'unite.required' => 'L\'unité est obligatoire.',
            'unite.in' => 'L\'unité sélectionnée est invalide.',
            'droit_annuel.numeric' => 'Le droit annuel doit être un nombre.',
            'droit_annuel.min' => 'Le droit annuel ne peut pas être négatif.',
            'droit_annuel.max' => 'Le droit annuel ne peut pas dépasser 365.',
            'duree_max.numeric' => 'La durée maximale doit être un nombre.',
            'duree_max.max' => 'La durée maximale ne peut pas dépasser 365.',
            'traitement_salarial.required' => 'Le traitement salarial est obligatoire.',
            'impact_conge_annuel.required' => 'L\'impact sur le congé annuel est obligatoire.',
            'impact_anciennete.required' => 'L\'impact sur l\'ancienneté est obligatoire.',
            'delai_justification_jours.integer' => 'Le délai doit être un entier.',
            'delai_justification_jours.max' => 'Le délai ne peut pas dépasser 90 jours.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code',
            'nom' => 'nom',
            'categorie' => 'catégorie',
            'unite' => 'unité',
            'droit_annuel' => 'droit annuel',
            'duree_max' => 'durée maximale',
            'reference_legale' => 'référence légale',
            'traitement_salarial' => 'traitement salarial',
            'impact_conge_annuel' => 'impact congé annuel',
            'impact_anciennete' => 'impact ancienneté',
            'delai_justification_jours' => 'délai de justification',
        ];
    }
}