<?php

namespace App\Domain\Classification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferentielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('classification.manage');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:160'],
            'nom' => ['required', 'string', 'max:255'],
            'type_referentiel' => ['required', 'string', 'max:60'],
            'niveau_source' => ['required', 'string', 'max:80'],
            'intitule_source' => ['nullable', 'string', 'max:255'],
            'reference_source' => ['nullable', 'string', 'max:255'],
            'portee' => ['nullable', 'string'],
            'priorite' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'debut_effet' => ['nullable', 'date'],
            'fin_effet' => ['nullable', 'date', 'after_or_equal:debut_effet'],
            'actif' => ['boolean'],
        ];
    }
}