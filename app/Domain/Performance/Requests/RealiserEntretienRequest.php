<?php

namespace App\Domain\Performance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RealiserEntretienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('performance.manage') || $this->user()->peut('performance.evaluer');
    }

    public function rules(): array
    {
        return [
            'note_manager' => ['required', 'numeric', 'min:0', 'max:20'],
            'points_forts' => ['nullable', 'string', 'max:3000'],
            'besoins_developpement' => ['nullable', 'string', 'max:3000'],
            'commentaire_manager' => ['nullable', 'string', 'max:3000'],
            'action_amelioration' => ['nullable', 'string', 'max:3000'],
            'date_echeance_amelioration' => ['nullable', 'date', 'after:today'],
            'date_entretien' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'note_manager.required' => 'La note du manager est obligatoire.',
            'note_manager.numeric' => 'La note doit être un nombre.',
            'note_manager.min' => 'La note ne peut pas être négative.',
            'note_manager.max' => 'La note ne peut pas dépasser 20.',
            'date_echeance_amelioration.date' => 'La date d\'échéance doit être une date valide.',
            'date_echeance_amelioration.after' => 'La date d\'échéance doit être future.',
        ];
    }

    public function attributes(): array
    {
        return [
            'note_manager' => 'note du manager',
            'points_forts' => 'points forts',
            'besoins_developpement' => 'besoins de développement',
            'commentaire_manager' => 'commentaire manager',
            'action_amelioration' => 'action d\'amélioration',
        ];
    }
}