<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\CauseFinInterim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloturerInterimInterneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        return [
            'cause_fin' => ['required', Rule::enum(CauseFinInterim::class)->except([CauseFinInterim::DECISION])],
            'date_fin_reelle' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
            'piece' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'cause_fin.required' => 'La cause de fin est obligatoire.',
            'cause_fin.enum' => 'La cause de fin sélectionnée est invalide.',
            'date_fin_reelle.required' => 'La date de fin réelle est obligatoire.',
            'piece.mimes' => 'La pièce doit être un fichier PDF, PNG ou JPEG.',
            'piece.max' => 'La pièce ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cause_fin' => 'cause de fin',
            'date_fin_reelle' => 'date de fin réelle',
            'commentaire' => 'commentaire',
            'piece' => 'pièce jointe',
        ];
    }
}
