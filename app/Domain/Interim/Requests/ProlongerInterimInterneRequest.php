<?php

namespace App\Domain\Interim\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProlongerInterimInterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'date_fin_prevue' => ['required', 'date'],
            'date_fin_motif' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
            'piece' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_fin_prevue.required' => 'La nouvelle date de fin est obligatoire.',
            'piece.required' => 'La pièce justificative de la prolongation est obligatoire.',
            'piece.mimes' => 'La pièce doit être un fichier PDF, PNG ou JPEG.',
            'piece.max' => 'La pièce ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_fin_prevue' => 'nouvelle date de fin',
            'date_fin_motif' => 'date de fin du motif',
            'commentaire' => 'commentaire',
            'piece' => 'pièce justificative',
        ];
    }
}
