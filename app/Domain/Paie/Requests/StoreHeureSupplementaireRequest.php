<?php

namespace App\Domain\Paie\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeureSupplementaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('paie.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'reference' => ['required', 'string', 'max:160'],
            'debut_travail' => ['required', 'date'],
            'fin_travail' => ['required', 'date', 'after_or_equal:debut_travail'],
            'periode_paiement_id' => ['required', 'exists:periodes_paie,id'],
            'periode_origine_id' => ['nullable', 'exists:periodes_paie,id'],
            'heures_hs20' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'heures_hs40' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'heures_hs65_jour' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'heures_hs65_nuit' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'heures_hs100' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'est_rappel' => ['boolean'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'motif_retard' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'reference.required' => 'La référence est obligatoire.',
            'reference.max' => 'La référence ne doit pas dépasser 160 caractères.',
            'debut_travail.required' => 'La date de début est obligatoire.',
            'debut_travail.date' => 'La date de début doit être une date valide.',
            'fin_travail.required' => 'La date de fin est obligatoire.',
            'fin_travail.date' => 'La date de fin doit être une date valide.',
            'fin_travail.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'periode_paiement_id.required' => 'La période de paiement est obligatoire.',
            'periode_paiement_id.exists' => 'La période de paiement sélectionnée n\'existe pas.',
            'heures_hs20.numeric' => 'Les heures HS 20 % doivent être un nombre.',
            'heures_hs20.min' => 'Les heures HS 20 % ne peuvent pas être négatives.',
            'heures_hs20.max' => 'Les heures HS 20 % ne peuvent pas dépasser 500.',
            'heures_hs40.numeric' => 'Les heures HS 40 % doivent être un nombre.',
            'heures_hs40.max' => 'Les heures HS 40 % ne peuvent pas dépasser 500.',
            'heures_hs65_jour.numeric' => 'Les heures HS 65 % jour doivent être un nombre.',
            'heures_hs65_jour.max' => 'Les heures HS 65 % jour ne peuvent pas dépasser 500.',
            'heures_hs65_nuit.numeric' => 'Les heures HS 65 % nuit doivent être un nombre.',
            'heures_hs65_nuit.max' => 'Les heures HS 65 % nuit ne peuvent pas dépasser 500.',
            'heures_hs100.numeric' => 'Les heures HS 100 % doivent être un nombre.',
            'heures_hs100.max' => 'Les heures HS 100 % ne peuvent pas dépasser 500.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'reference' => 'référence',
            'debut_travail' => 'date de début',
            'fin_travail' => 'date de fin',
            'periode_paiement_id' => 'période de paiement',
            'periode_origine_id' => 'période d\'origine',
            'heures_hs20' => 'heures HS 20 %',
            'heures_hs40' => 'heures HS 40 %',
            'heures_hs65_jour' => 'heures HS 65 % jour',
            'heures_hs65_nuit' => 'heures HS 65 % nuit',
            'heures_hs100' => 'heures HS 100 %',
            'motif' => 'motif',
            'motif_retard' => 'motif de retard',
        ];
    }
}