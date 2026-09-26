<?php

namespace App\Domain\Organisation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('organisation.manage');
    }

    public function rules(): array
    {
        return [
            'type_structure_id' => ['required', 'exists:types_structures,id'],
            'parent_id' => ['nullable', 'exists:structures,id'],
            'code' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:255'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'centre_cout' => ['nullable', 'string', 'max:200'],
            'actif' => ['boolean'],
        ];
    }
}