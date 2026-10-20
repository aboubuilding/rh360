<?php

namespace App\Domain\Interim\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// RG-TT-04 — facture de l'agence, seule trace financière d'une mission.
class StoreFactureInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $agence = $this->route('agence');

        return [
            'mission_interim_id' => ['nullable', Rule::exists('missions_interim', 'id')->where('entreprise_interim_id', $agence?->id)],
            'numero_facture' => ['required', 'string', 'max:120',
                Rule::unique('factures_interim', 'numero_facture')->where('entreprise_interim_id', $agence?->id)],
            'date_facture' => ['required', 'date'],
            'periode_debut' => ['nullable', 'date'],
            'periode_fin' => ['nullable', 'date', 'after_or_equal:periode_debut'],
            'montant' => ['required', 'integer', 'min:0'],
            'piece' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_facture.required' => 'Le numéro de facture est obligatoire.',
            'numero_facture.unique' => 'Cette facture est déjà enregistrée pour cette agence.',
            'date_facture.required' => 'La date de facture est obligatoire.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.integer' => 'Le montant est un entier en FCFA.',
            'mission_interim_id.exists' => 'La mission ne relève pas de cette agence.',
        ];
    }
}
