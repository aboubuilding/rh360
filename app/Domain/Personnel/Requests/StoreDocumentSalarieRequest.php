<?php

namespace App\Domain\Personnel\Requests;

use App\Domain\Personnel\Enums\TypeDocumentSalarie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentSalarieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('salaries.manage');
    }

    public function rules(): array
    {
        return [
            'type_document' => ['required', Rule::in(array_column(TypeDocumentSalarie::cases(), 'value'))],
            'fichier' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
            'date_document' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_document'],
            'observations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_document.required' => 'Le type de document est obligatoire.',
            'type_document.in' => 'Le type de document sélectionné est invalide.',

            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.file' => 'Le fichier est invalide.',
            'fichier.mimes' => 'Le fichier doit être au format PDF, PNG, JPG ou JPEG.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 8 Mo.',

            'date_document.date' => 'La date du document doit être une date valide.',
            'date_expiration.date' => 'La date d\'expiration doit être une date valide.',
            'date_expiration.after_or_equal' => 'La date d\'expiration doit être postérieure ou égale à la date du document.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_document' => 'type de document',
            'fichier' => 'fichier',
            'date_document' => 'date du document',
            'date_expiration' => 'date d\'expiration',
            'observations' => 'observations',
        ];
    }
}