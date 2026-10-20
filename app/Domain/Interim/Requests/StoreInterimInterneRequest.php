<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\MotifInterim;
use App\Domain\Interim\Services\ValidateurInterimInterne;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInterimInterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'poste_occupe_id' => ['required', 'exists:postes,id'],
            'titulaire_id' => ['nullable', 'exists:salaries,id'],
            'absence_titulaire_id' => ['nullable', 'exists:absences,id'],
            'motif' => ['required', Rule::enum(MotifInterim::class)],
            'precision_motif' => ['nullable', 'string', 'max:2000'],
            'date_debut' => ['required', 'date'],
            'date_fin_prevue' => ['required', 'date', 'after_or_equal:date_debut'],
            'date_fin_motif' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'piece' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    // RG-INT-01, RG-INT-03, RG-INT-04, RG-INT-05 — contrôles métier après les règles de forme.
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $controle = app(ValidateurInterimInterne::class)->controler($validator->validated());
                foreach ($controle->erreurs as $champ => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($champ, $message);
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'poste_occupe_id.required' => 'Le poste occupé est obligatoire.',
            'motif.required' => 'Le motif de l\'intérim est obligatoire.',
            'motif.enum' => 'Le motif sélectionné est invalide.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin_prevue.required' => 'La date de fin prévue est obligatoire.',
            'date_fin_prevue.after_or_equal' => 'La date de fin prévue doit être postérieure à la date de début.',
            'date_fin_motif.after_or_equal' => 'La date de fin du motif doit être postérieure à la date de début.',
            'piece.mimes' => 'La pièce doit être un fichier PDF, PNG ou JPEG.',
            'piece.max' => 'La pièce ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'poste_occupe_id' => 'poste occupé',
            'titulaire_id' => 'titulaire remplacé',
            'absence_titulaire_id' => 'absence du titulaire',
            'motif' => 'motif',
            'precision_motif' => 'précision du motif',
            'date_debut' => 'date de début',
            'date_fin_prevue' => 'date de fin prévue',
            'date_fin_motif' => 'date de fin du motif',
            'piece' => 'pièce jointe',
        ];
    }
}
