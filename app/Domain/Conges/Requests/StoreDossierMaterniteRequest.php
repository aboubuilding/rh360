<?php

namespace App\Domain\Conges\Requests;

use App\Domain\Conges\Enums\StatutDossierMaternite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDossierMaterniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('sensitive.social_health')
            && $this->user()->peut('conges.manage');
    }

    public function rules(): array
    {
        return [
            'salarie_id' => ['required', 'exists:salaries,id'],
            'date_declaration' => ['nullable', 'date'],
            'date_prevue_accouchement' => ['nullable', 'date', 'after:date_declaration'],
            'risque_poste_identifie' => ['boolean'],
            'amenagement_temporaire' => ['nullable', 'string', 'max:2000'],
            'statut' => ['nullable', Rule::in(array_column(StatutDossierMaternite::cases(), 'value'))],
            'date_consultation_1' => ['nullable', 'date'],
            'date_consultation_2' => ['nullable', 'date'],
            'date_consultation_3' => ['nullable', 'date'],
            'date_debut_conge' => ['nullable', 'date'],
            'date_reprise_effective' => ['nullable', 'date', 'after:date_debut_conge'],
            'reference_acte' => ['nullable', 'string', 'max:255'],
            'date_acte' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:4000'],
            'certificat_medical' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'salarie_id.required' => 'La salariée est obligatoire.',
            'salarie_id.exists' => 'La salariée sélectionnée n\'existe pas.',
            'date_declaration.date' => 'La date de déclaration doit être une date valide.',
            'date_prevue_accouchement.date' => 'La date prévue d\'accouchement doit être une date valide.',
            'date_prevue_accouchement.after' => 'La date d\'accouchement doit être postérieure à la déclaration.',
            'date_reprise_effective.after' => 'La date de reprise doit être postérieure à la date de début du congé.',
            'certificat_medical.file' => 'Le certificat est invalide.',
            'certificat_medical.mimes' => 'Le certificat doit être au format PDF, PNG, JPG ou JPEG.',
            'certificat_medical.max' => 'Le certificat ne doit pas dépasser 8 Mo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salarie_id' => 'salariée',
            'date_declaration' => 'date de déclaration',
            'date_prevue_accouchement' => 'date prévue d\'accouchement',
            'amenagement_temporaire' => 'aménagement temporaire',
            'date_consultation_1' => 'consultation prénatale 1',
            'date_consultation_2' => 'consultation prénatale 2',
            'date_consultation_3' => 'consultation prénatale 3',
            'date_debut_conge' => 'date de début du congé',
            'date_reprise_effective' => 'date de reprise effective',
            'certificat_medical' => 'certificat médical',
        ];
    }
}