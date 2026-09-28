<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\TypeEvenementSecurite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvenementSecuriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('safety.manage');
    }

    public function rules(): array
    {
        return [
            'type_evenement' => ['required', Rule::in(array_column(TypeEvenementSecurite::cases(), 'value'))],
            'date_survenance' => ['required', 'date', 'before_or_equal:today'],
            'heure_survenance' => ['nullable', 'string', 'max:50'],
            'date_declaration' => ['nullable', 'date'],
            'intitule' => ['required', 'string', 'max:255'],
            'localisation' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'mesures_immediates' => ['nullable', 'string', 'max:3000'],
            'priorite' => ['nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:salaries,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_evenement.required' => 'Le type d\'événement est obligatoire.',
            'type_evenement.in' => 'Le type d\'événement sélectionné est invalide.',
            'date_survenance.required' => 'La date de survenance est obligatoire.',
            'date_survenance.date' => 'La date de survenance doit être une date valide.',
            'date_survenance.before_or_equal' => 'La date de survenance ne peut pas être dans le futur.',
            'intitule.required' => 'L\'intitulé est obligatoire.',
            'intitule.max' => 'L\'intitulé ne doit pas dépasser 255 caractères.',
            'localisation.required' => 'La localisation est obligatoire.',
            'localisation.max' => 'La localisation ne doit pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne doit pas dépasser 5000 caractères.',
            'mesures_immediates.max' => 'Les mesures immédiates ne doivent pas dépasser 3000 caractères.',
            'participants.*.exists' => 'Un participant sélectionné n\'existe pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type_evenement' => 'type d\'événement',
            'date_survenance' => 'date de survenance',
            'heure_survenance' => 'heure de survenance',
            'date_declaration' => 'date de déclaration',
            'intitule' => 'intitulé',
            'localisation' => 'localisation',
            'description' => 'description',
            'mesures_immediates' => 'mesures immédiates',
            'participants' => 'participants',
        ];
    }
}