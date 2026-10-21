<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModelePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    // Les lignes laissées vides (aucune rubrique choisie) sont ignorées.
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rubriques' => collect($this->input('rubriques', []))
                ->filter(fn ($l) => is_array($l) && ! empty($l['rubrique_id']))
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $modeleId = $this->route('modele')?->id;

        return [
            'categorie_id' => [
                'required', 'exists:categories_classification,id',
                Rule::unique('modeles_paie', 'categorie_id')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($modeleId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'actif' => ['boolean'],
            'rubriques' => ['nullable', 'array'],
            // Une rubrique ne figure qu'une fois dans un modèle.
            'rubriques.*.rubrique_id' => ['required', 'distinct', Rule::exists('rubriques_paie', 'id')->where('entreprise_id', $this->user()->entreprise_id)],
            'rubriques.*.montant_defaut' => ['nullable', 'numeric', 'min:0'],
            'rubriques.*.obligatoire' => ['boolean'],
            'rubriques.*.ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_id.required' => 'La catégorie est obligatoire.',
            'categorie_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'categorie_id.unique' => 'Un modèle existe déjà pour cette catégorie.',
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'rubriques.*.rubrique_id.required' => 'La rubrique est obligatoire.',
            'rubriques.*.rubrique_id.exists' => 'Une rubrique sélectionnée n\'existe pas.',
            'rubriques.*.rubrique_id.distinct' => 'Cette rubrique figure déjà dans le modèle.',
            'rubriques.*.montant_defaut.numeric' => 'Le montant par défaut doit être un nombre.',
            'rubriques.*.ordre.integer' => 'L\'ordre doit être un entier.',
        ];
    }
}