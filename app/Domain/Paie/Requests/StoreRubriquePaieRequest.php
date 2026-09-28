<?php

namespace App\Domain\Paie\Requests;

use App\Domain\Paie\Enums\ModeCalculRubrique;
use App\Domain\Paie\Enums\NatureRubrique;
use App\Domain\Paie\Enums\RecurrenceRubrique;
use App\Domain\Paie\Enums\TraitementFiscal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRubriquePaieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        $rubriqueId = $this->route('rubrique')?->id;

        return [
            'code' => [
                'required', 'string', 'max:80',
                Rule::unique('rubriques_paie', 'code')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($rubriqueId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'nature' => ['required', Rule::in(array_column(NatureRubrique::cases(), 'value'))],
            'recurrence' => ['required', Rule::in(array_column(RecurrenceRubrique::cases(), 'value'))],
            'mode_calcul' => ['required', Rule::in(array_column(ModeCalculRubrique::cases(), 'value'))],
            'taux' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'montant_defaut' => ['nullable', 'numeric', 'min:0'],
            'imposable' => ['boolean'],
            'traitement_fiscal' => ['required', Rule::in(array_column(TraitementFiscal::cases(), 'value'))],
            'pourcentage_imposable' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'methode_evaluation' => ['nullable', 'string', 'max:60'],
            'justificatif_requis' => ['boolean'],
            'reference_fiscale' => ['nullable', 'string', 'max:255'],
            'soumis_cotisation' => ['boolean'],
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
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'nature.required' => 'La nature est obligatoire.',
            'nature.in' => 'La nature sélectionnée est invalide.',
            'recurrence.required' => 'La récurrence est obligatoire.',
            'recurrence.in' => 'La récurrence sélectionnée est invalide.',
            'mode_calcul.required' => 'Le mode de calcul est obligatoire.',
            'mode_calcul.in' => 'Le mode de calcul sélectionné est invalide.',
            'taux.numeric' => 'Le taux doit être un nombre.',
            'taux.min' => 'Le taux ne peut pas être négatif.',
            'taux.max' => 'Le taux ne peut pas dépasser 100.',
            'montant_defaut.numeric' => 'Le montant par défaut doit être un nombre.',
            'traitement_fiscal.required' => 'Le traitement fiscal est obligatoire.',
            'traitement_fiscal.in' => 'Le traitement fiscal sélectionné est invalide.',
            'pourcentage_imposable.numeric' => 'Le pourcentage imposable doit être un nombre.',
            'pourcentage_imposable.max' => 'Le pourcentage imposable ne peut pas dépasser 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'code',
            'nom' => 'nom',
            'nature' => 'nature',
            'recurrence' => 'récurrence',
            'mode_calcul' => 'mode de calcul',
            'taux' => 'taux',
            'montant_defaut' => 'montant par défaut',
            'traitement_fiscal' => 'traitement fiscal',
            'pourcentage_imposable' => 'pourcentage imposable',
        ];
    }
}