<?php

namespace App\Domain\Interim\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Actions sur une mission : checklist SST (RG-TT-07), renouvellement, fin, annulation.
 * Les règles dépendent de l'action portée par la route.
 */
class ActionMissionInterimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('carriere.manage');
    }

    public function rules(): array
    {
        $piece = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'];

        return match ($this->route()->getActionMethod()) {
            'checklist' => [
                'sst_accueil_le' => ['required', 'date'],
                'sst_epi_remis_le' => ['required', 'date'],
                'sst_consignes_le' => ['required', 'date'],
                'piece' => $piece,
            ],
            'renouveler' => [
                'date_fin' => ['required', 'date'],
                'commentaire' => ['nullable', 'string', 'max:2000'],
                'piece' => $piece,
            ],
            'terminer' => [
                'date_fin_reelle' => ['required', 'date'],
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
            'sst_accueil_le.required' => 'La date de l\'accueil sécurité est obligatoire.',
            'sst_epi_remis_le.required' => 'La date de remise des EPI est obligatoire.',
            'sst_consignes_le.required' => 'La date de remise des consignes du poste est obligatoire.',
            'date_fin.required' => 'La nouvelle date de fin est obligatoire.',
            'date_fin_reelle.required' => 'La date de fin réelle est obligatoire.',
            'motif_annulation.required' => 'Le motif d\'annulation est obligatoire.',
            'piece.mimes' => 'La pièce doit être un fichier PDF, PNG ou JPEG.',
            'piece.max' => 'La pièce ne doit pas dépasser 8 Mo.',
        ];
    }
}
