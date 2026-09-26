<?php

namespace App\Domain\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('organisation.manage');
    }

    public function rules(): array
    {
        return [
            'structure_id' => ['required', 'exists:structures,id'],
            'code' => ['required', 'string', 'max:100'],
            'intitule' => ['required', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:200'],
            'effectif_cible' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ];
    }
}