<?php

namespace App\Domain\Sst\Requests;

use App\Domain\Sst\Enums\AptitudeMedicale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenseignerVisiteMedicaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('health.manage')
            && $this->user()->peut('sensitive.social_health');
    }

    public function rules(): array
    {
        return [
            'date_realisation' => ['required', 'date'],
            'aptitude' => ['required', Rule::in(array_column(AptitudeMedicale::cases(), 'value'))],
            'reference_avis' => ['nullable', 'string', 'max:200'],
            'restrictions' => ['nullable', 'string', 'max:2000'],
            'prestataire' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_realisation.required' => 'La date de réalisation est obligatoire.',
            'date_realisation.date' => 'La date de réalisation doit être une date valide.',
            'aptitude.required' => 'L\'aptitude est obligatoire.',
            'aptitude.in' => 'L\'aptitude sélectionnée est invalide.',
            'reference_avis.max' => 'La référence de l\'avis ne doit pas dépasser 200 caractères.',
            'restrictions.max' => 'Les restrictions ne doivent pas dépasser 2000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_realisation' => 'date de réalisation',
            'aptitude' => 'aptitude',
            'reference_avis' => 'référence de l\'avis',
            'restrictions' => 'restrictions',
            'prestataire' => 'prestataire',
        ];
    }
}