<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\TypeMotifMission;
use App\Domain\Interim\Services\MissionInterimService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// RG-TT-02 — contenu d'une mission ; RG-TT-01, 05, 06 contrôlés après les règles de forme.
class StoreMissionInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $entrepriseId = $this->user()->entreprise_id;

        return [
            'entreprise_interim_id' => ['required', Rule::exists('entreprises_interim', 'id')->where('entreprise_id', $entrepriseId)],
            'interimaire_nom' => ['required', 'string', 'max:255'],
            'interimaire_prenoms' => ['required', 'string', 'max:255'],
            'interimaire_piece' => ['nullable', 'string', 'max:160'],
            'interimaire_telephone' => ['nullable', 'string', 'max:60'],
            'poste_id' => ['required', Rule::exists('postes', 'id')->where('entreprise_id', $entrepriseId)],
            'qualification_convenue' => ['required', 'string', 'max:255'],
            'travail_determine' => ['required', 'string', 'max:2000'],
            'type_motif' => ['required', Rule::enum(TypeMotifMission::class)],
            'motif' => ['required', 'string', 'max:2000'],
            'titulaire_remplace_id' => [
                Rule::requiredIf($this->input('type_motif') === TypeMotifMission::REMPLACEMENT_ABSENCE->value),
                'nullable', Rule::exists('salaries', 'id')->where('entreprise_id', $entrepriseId),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'salaire_convenu' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $controle = app(MissionInterimService::class)->controler($validator->validated());
                foreach ($controle['erreurs'] as $champ => $messages) {
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
            'entreprise_interim_id.required' => 'L\'entreprise de travail temporaire est obligatoire.',
            'interimaire_nom.required' => 'Le nom de l\'intérimaire est obligatoire.',
            'interimaire_prenoms.required' => 'Les prénoms de l\'intérimaire sont obligatoires.',
            'poste_id.required' => 'Le poste est obligatoire.',
            'qualification_convenue.required' => 'La qualification convenue est obligatoire.',
            'travail_determine.required' => 'Le travail déterminé confié à l\'intérimaire est obligatoire.',
            'type_motif.required' => 'La nature du motif est obligatoire.',
            'motif.required' => 'Le motif de recours est obligatoire.',
            'titulaire_remplace_id.required' => 'Le salarié remplacé est obligatoire pour un remplacement.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'salaire_convenu.integer' => 'Le salaire convenu est un montant entier en FCFA.',
        ];
    }

    public function attributes(): array
    {
        return [
            'entreprise_interim_id' => 'agence d\'intérim',
            'interimaire_nom' => 'nom de l\'intérimaire',
            'interimaire_prenoms' => 'prénoms de l\'intérimaire',
            'poste_id' => 'poste',
            'qualification_convenue' => 'qualification convenue',
            'travail_determine' => 'travail déterminé',
            'type_motif' => 'nature du motif',
            'titulaire_remplace_id' => 'salarié remplacé',
            'salaire_convenu' => 'salaire convenu',
        ];
    }
}
