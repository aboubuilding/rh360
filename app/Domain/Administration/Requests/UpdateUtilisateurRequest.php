<?php

namespace App\Domain\Administration\Requests;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('admin.utilisateurs.manage');
    }

    public function rules(): array
    {
        $utilisateur = $this->route('utilisateur');

        return [
            'nom_complet' => ['required', 'string', 'max:255'],
            'identifiant' => [
                'required', 'string', 'max:200',
                Rule::unique('utilisateurs', 'identifiant')
                    ->where('entreprise_id', $this->user()->entreprise_id)
                    ->ignore($utilisateur->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['required', Rule::in(array_keys(Utilisateur::roles()))],
            'actif' => ['boolean'],
        ];
    }
}