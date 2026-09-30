<?php

namespace App\Domain\Administration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntrepriseRequest extends FormRequest
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
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'La raison sociale est obligatoire.',
            'nom.max' => 'La raison sociale ne doit pas dépasser 255 caractères.',
            'sigle.max' => 'Le sigle ne doit pas dépasser 100 caractères.',
            'forme_juridique.max' => 'La forme juridique ne doit pas dépasser 200 caractères.',
            'nif.max' => 'Le NIF ne doit pas dépasser 200 caractères.',
            'numero_employeur_cnss.max' => 'Le numéro employeur CNSS ne doit pas dépasser 200 caractères.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne doit pas dépasser 255 caractères.',
            'date_bascule.date' => 'La date de bascule doit être une date valide.',
            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format PNG, JPG ou JPEG.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'raison sociale',
            'sigle' => 'sigle',
            'forme_juridique' => 'forme juridique',
            'nif' => 'NIF',
            'numero_employeur_cnss' => 'numéro employeur CNSS',
            'telephone' => 'téléphone',
            'email' => 'email',
            'date_bascule' => 'date de bascule',
            'nom_signataire' => 'nom du signataire',
            'fonction_signataire' => 'fonction du signataire',
            'lieu_signature' => 'lieu de signature',
            'logo' => 'logo',
        ];
    }
}