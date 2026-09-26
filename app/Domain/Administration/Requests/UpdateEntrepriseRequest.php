<?php

namespace App\Domain\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntrepriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('admin.entreprise.manage');
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:100'],
            'forme_juridique' => ['nullable', 'string', 'max:200'],
            'nif' => ['nullable', 'string', 'max:200'],
            'numero_employeur_cnss' => ['nullable', 'string', 'max:200'],
            'secteur' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:200'],
            'pays' => ['nullable', 'string', 'max:200'],
            'telephone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'devise' => ['nullable', 'string', 'max:50'],
            'date_bascule' => ['nullable', 'date'],
            'direction_emettrice' => ['nullable', 'string', 'max:255'],
            'service_emetteur' => ['nullable', 'string', 'max:255'],
            'texte_en_tete' => ['nullable', 'string'],
            'texte_pied_page' => ['nullable', 'string'],
            'nom_signataire' => ['nullable', 'string', 'max:255'],
            'fonction_signataire' => ['nullable', 'string', 'max:255'],
            'lieu_signature' => ['nullable', 'string', 'max:240'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ];
    }
}