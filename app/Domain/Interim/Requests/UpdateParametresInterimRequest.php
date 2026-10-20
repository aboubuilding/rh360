<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Services\CatalogueParametresInterim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParametresInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $regles = [];

        foreach (CatalogueParametresInterim::definitions() as $cle => $definition) {
            $min = $definition['min'] ?? 0;

            $regles[$cle] = match ($definition['type']) {
                'entier' => ['required', 'integer', "min:{$min}"],
                'entier_facultatif' => ['nullable', 'integer', "min:{$min}"],
                'booleen' => ['required', 'boolean'],
                'choix' => ['required', Rule::in(array_keys($definition['options']))],
                'referentiel' => ['nullable', 'exists:referentiels_classification,id'],
                'referentiels' => ['nullable', 'array'],
                default => ['required', 'string', 'max:160'],
            };

            if ($definition['type'] === 'referentiels') {
                $regles["{$cle}.*"] = ['exists:referentiels_classification,id'];
            }
        }

        return $regles;
    }

    public function attributes(): array
    {
        return array_map(fn ($d) => mb_strtolower($d['libelle']), CatalogueParametresInterim::definitions());
    }
}
