<?php

namespace App\Domain\Administration\Requests;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionsRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('admin.permissions.manage');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_keys(Utilisateur::roles()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:240'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle sélectionné est invalide.',
            'permissions.array' => 'Les permissions doivent être une liste.',
            'permissions.*.max' => 'Une permission ne doit pas dépasser 240 caractères.',
        ];
    }
}