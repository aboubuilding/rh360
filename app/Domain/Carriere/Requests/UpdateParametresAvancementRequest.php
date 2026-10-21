<?php

namespace App\Domain\Carriere\Requests;

use App\Domain\Carriere\Services\CatalogueParametresAvancement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParametresAvancementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $regles = [];

        foreach (CatalogueParametresAvancement::definitions() as $cle => $definition) {
            $min = $definition['min'] ?? 0;

            $regles[$cle] = match ($definition['type']) {
                'entier' => ['required', 'integer', "min:{$min}"],
                'decimal' => ['required', 'numeric', "min:{$min}"],
                'booleen' => ['required', 'boolean'],
                'choix' => ['required', Rule::in(array_keys($definition['options']))],
                'types_conges' => ['nullable', 'array'],
                default => ['required', 'string', 'max:160'],
            };

            if ($definition['type'] === 'types_conges') {
                $regles["{$cle}.*"] = ['exists:types_conges,code'];
            }
        }

        // La limite de la fenêtre de catégorie suit son ouverture.
        $regles['fenetre_categorie_limite_mois'][] = 'gte:fenetre_categorie_ouverture_mois';

        return $regles;
    }

    public function attributes(): array
    {
        return array_map(fn ($d) => mb_strtolower($d['libelle']), CatalogueParametresAvancement::definitions());
    }
}
