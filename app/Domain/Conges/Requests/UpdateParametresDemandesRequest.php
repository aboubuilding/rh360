<?php

namespace App\Domain\Conges\Requests;

use App\Domain\Conges\Services\CatalogueParametresDemandes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParametresDemandesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('conges.manage');
    }

    public function rules(): array
    {
        $regles = [];

        foreach (CatalogueParametresDemandes::definitions() as $cle => $definition) {
            $bornes = array_filter([
                isset($definition['min']) ? "min:{$definition['min']}" : 'min:0',
                isset($definition['max']) ? "max:{$definition['max']}" : null,
            ]);

            $regles[$cle] = match ($definition['type']) {
                'entier' => ['required', 'integer', ...$bornes],
                'entier_facultatif' => ['nullable', 'integer', ...$bornes],
                'decimal' => ['required', 'numeric', ...$bornes],
                'booleen' => ['required', 'boolean'],
                'choix' => ['required', Rule::in(array_keys($definition['options']))],
                default => ['required', 'string', 'max:160'],
            };
        }

        // Les travaux urgents allongent la journée, ils ne la raccourcissent pas.
        $regles['hs_max_jour_urgence'][] = 'gte:hs_max_jour';
        $regles['maternite_semaines_apres'][] = 'lt:maternite_semaines';

        return $regles;
    }

    public function attributes(): array
    {
        return array_map(fn ($d) => mb_strtolower($d['libelle']), CatalogueParametresDemandes::definitions());
    }
}
