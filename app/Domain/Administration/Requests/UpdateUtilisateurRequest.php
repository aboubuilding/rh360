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

    public function messages(): array
    {
        return [
            'nom_complet.required' => 'Le nom complet est obligatoire.',
            'nom_complet.max' => 'Le nom complet ne doit pas dépasser 255 caractères.',
            'identifiant.required' => 'L\'identifiant de connexion est obligatoire.',
            'identifiant.unique' => 'Cet identifiant est déjà utilisé dans votre entreprise.',
            'identifiant.max' => 'L\'identifiant ne doit pas dépasser 200 caractères.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.max' => 'L\'adresse email ne doit pas dépasser 255 caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné est invalide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nom_complet' => 'nom complet',
            'identifiant' => 'identifiant',
            'email' => 'email',
            'password' => 'mot de passe',
            'role' => 'rôle',
        ];
    }
}