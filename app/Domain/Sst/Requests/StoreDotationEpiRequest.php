<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\CategorieEpi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDotationEpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('ppe.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'risque_id' => ['nullable', 'exists:risques,id'],
            'categorie' => ['required', Rule::in(array_column(CategorieEpi::cases(), 'value'))],
            'intitule' => ['required', 'string', 'max:255'],
            'quantite' => ['required', 'integer', 'min:1', 'max:1000'],
            'unite' => ['required', 'string', 'max:80'],
            'numero_serie' => ['nullable', 'string', 'max:200'],
            'taille' => ['nullable', 'string', 'max:120'],
            'date_remise' => ['required', 'date'],
            'date_expiration' => ['nullable', 'date', 'after:date_remise'],
            'date_verification' => ['nullable', 'date'],
            'emetteur' => ['required', 'string', 'max:255'],
            'reference_recu' => ['nullable', 'string', 'max:240'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'Le salarié est obligatoire.',
            'salarie_id.exists' => 'Le salarié sélectionné n\'existe pas.',
            'risque_id.exists' => 'Le risque sélectionné n\'existe pas.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.in' => 'La catégorie sélectionnée est invalide.',
            'intitule.required' => 'L\'intitulé de l\'équipement est obligatoire.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.min' => 'La quantité doit être au moins de 1.',
            'unite.required' => 'L\'unité est obligatoire.',
            'date_remise.required' => 'La date de remise est obligatoire.',
            'date_expiration.after' => 'La date d\'expiration doit être postérieure à la date de remise.',
            'emetteur.required' => 'L\'émetteur est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salarié',
            'risque_id' => 'risque',
            'categorie' => 'catégorie',
            'intitule' => 'intitulé',
            'quantite' => 'quantité',
            'unite' => 'unité',
            'numero_serie' => 'numéro de série',
            'taille' => 'taille',
            'date_remise' => 'date de remise',
            'date_expiration' => 'date d\'expiration',
            'date_verification' => 'date de vérification',
            'emetteur' => 'émetteur',
            'reference_recu' => 'référence du reçu',
        ];
    }
}