<?php

namespace App\Domain\Interim\Requests;

use App\Domain\Interim\Enums\TypePiecePret;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Actions sur un prêt : pièce obligatoire, refus du salarié, refacturation, retour, annulation.
 */
class ActionPretMainOeuvreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $fichier = ['file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'];

        return match ($this->route()->getActionMethod()) {
            'piece' => [
                'type_piece' => ['required', Rule::enum(TypePiecePret::class)],
                'date_signature' => ['required', 'date'],
                'fichier' => array_merge(['required'], $fichier),
            ],
            'refus' => [
                'date_reponse_salarie' => ['required', 'date'],
                'commentaire' => ['nullable', 'string', 'max:2000'],
                'fichier' => array_merge(['nullable'], $fichier),
            ],
            'refacturation' => [
                'periode_debut' => ['required', 'date'],
                'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'],
                'salaires' => ['required', 'integer', 'min:0'],
                'charges_sociales' => ['required', 'integer', 'min:0'],
                'frais_professionnels' => ['nullable', 'integer', 'min:0'],
                'montant_refacture' => ['required', 'integer', 'min:0'],
            ],
            'retour' => [
                'date_retour' => ['required', 'date'],
                'poste_retour_id' => ['nullable', Rule::exists('postes', 'id')->where('entreprise_id', $this->user()->entreprise_id)],
                'commentaire' => ['nullable', 'string', 'max:2000'],
            ],
            'annuler' => [
                'motif_annulation' => ['required', 'string', 'max:2000'],
            ],
            default => [],
        };
    }

    public function messages(): array
    {
        return [
            'type_piece.required' => 'Le type de pièce est obligatoire.',
            'date_signature.required' => 'La date de signature est obligatoire.',
            'fichier.required' => 'Le document signé est obligatoire.',
            'fichier.mimes' => 'Le document doit être un fichier PDF, PNG ou JPEG.',
            'fichier.max' => 'Le document ne doit pas dépasser 8 Mo.',
            'date_reponse_salarie.required' => 'La date du refus est obligatoire.',
            'salaires.required' => 'Le montant des salaires est obligatoire.',
            'charges_sociales.required' => 'Le montant des charges sociales est obligatoire.',
            'montant_refacture.required' => 'Le montant refacturé est obligatoire.',
            'date_retour.required' => 'La date de retour est obligatoire.',
            'motif_annulation.required' => 'Le motif d\'annulation est obligatoire.',
        ];
    }
}
