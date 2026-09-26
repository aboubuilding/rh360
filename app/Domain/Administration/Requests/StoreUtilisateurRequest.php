<?php

namespace App\Domain\Administration\Requests;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('admin.utilisateurs.manage');
    }

    public function rules(): array
    {
        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'identifiant' => [
                'required', 'string', 'max:200',
                Rule::unique('utilisateurs', 'identifiant')
                    ->where('entreprise_id', $this->user()->entreprise_id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['required', Rule::in(array_keys(Utilisateur::roles()))],
            'actif' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['actif' => $this->boolean('actif', true)]);
    }
}