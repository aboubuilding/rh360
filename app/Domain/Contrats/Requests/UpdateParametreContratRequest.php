<?php

namespace App\Domain\Contrats\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParametreContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.validate');
    }

    public function rules(): array
    {
        return [
            'seuils' => ['required', 'array', 'min:1'],
            'seuils.*' => ['integer', 'min:0', 'max:365'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'in:rh,drh,manager,direction'],
        ];
    }

    public function messages(): array
    {
        return [
            'seuils.required' => 'Au moins un seuil est obligatoire.',
            'seuils.array' => 'Les seuils doivent être une liste.',
            'seuils.*.integer' => 'Chaque seuil doit être un nombre entier.',
            'seuils.*.min' => 'Un seuil ne peut pas être négatif.',
            'seuils.*.max' => 'Un seuil ne peut pas dépasser 365 jours.',
            'roles.required' => 'Au moins un rôle est obligatoire.',
            'roles.array' => 'Les rôles doivent être une liste.',
            'roles.*.in' => 'Un rôle sélectionné est invalide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'seuils' => 'seuils',
            'roles' => 'rôles',
        ];
    }
}