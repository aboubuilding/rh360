<?php

namespace App\Domain\Formation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImporterPlanFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('formation.manage');
    }

    public function rules(): array
    {
        return [
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.mimes' => 'Le fichier doit être au format XLSX, XLS ou CSV.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}