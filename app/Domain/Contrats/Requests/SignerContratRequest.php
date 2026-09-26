<?php

namespace App\Domain\Contrats\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignerContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.sign');
    }

    public function rules(): array
    {
        return [
            'date_signature' => ['required', 'date'],
            'reference_signee' => ['required', 'string', 'max:240'],
            'piece' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_signature.required' => 'La date de signature est obligatoire.',
            'date_signature.date' => 'La date de signature doit être une date valide.',
            'reference_signee.required' => 'La référence du document signé est obligatoire.',
            'reference_signee.max' => 'La référence ne doit pas dépasser 240 caractères.',
            'piece.file' => 'Le fichier joint est invalide.',
            'piece.mimes' => 'Le fichier doit être au format PDF, PNG, JPG ou JPEG.',
            'piece.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_signature' => 'date de signature',
            'reference_signee' => 'référence signée',
            'piece' => 'pièce jointe',
        ];
    }
}