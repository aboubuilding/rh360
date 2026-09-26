<?php

namespace App\Domain\Contrats\Requests;

use App\Domain\Contrats\Enums\ObjetPieceContrat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePieceContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('contrats.manage');
    }

    public function rules(): array
    {
        return [
            'objet' => ['required', Rule::in(array_column(ObjetPieceContrat::cases(), 'value'))],
            'libelle' => ['nullable', 'string', 'max:255'],
            'fichier' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'objet.required' => 'L\'objet de la pièce est obligatoire.',
            'objet.in' => 'L\'objet de la pièce est invalide.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères.',
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.file' => 'Le fichier est invalide.',
            'fichier.mimes' => 'Le fichier doit être au format PDF, PNG, JPG ou JPEG.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'objet' => 'objet',
            'libelle' => 'libellé',
            'fichier' => 'fichier',
        ];
    }
}