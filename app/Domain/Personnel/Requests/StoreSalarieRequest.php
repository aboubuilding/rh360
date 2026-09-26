<?php

namespace App\Domain\Personnel\Requests;

use App\Domain\Personnel\Enums\StatutEmploi;
use App\Domain\Personnel\Enums\TypePieceIdentite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalarieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->peut('salaries.manage');
    }

    public function rules(): array
    {
        $entrepriseId = $this->user()->entreprise_id;

        return [
            // Identité
            'nom' => ['required', 'string', 'max:255'],
            'prenoms' => ['required', 'string', 'max:255'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:200'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'type_piece' => ['nullable', Rule::in(array_column(TypePieceIdentite::cases(), 'value'))],
            'numero_piece' => ['nullable', 'string', 'max:255'],
            'date_expiration_piece' => ['nullable', 'date'],

            // Coordonnées
            'telephone_principal' => ['nullable', 'string', 'max:160'],
            'telephone_secondaire' => ['nullable', 'string', 'max:160'],
            'email_personnel' => ['nullable', 'email', 'max:255'],
            'email_professionnel' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:200'],
            'pays_residence' => ['nullable', 'string', 'max:200'],
            'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Famille
            'situation_matrimoniale' => ['nullable', 'string', 'max:160'],
            'contact_urgence_nom' => ['nullable', 'string', 'max:255'],
            'contact_urgence_lien' => ['nullable', 'string', 'max:200'],
            'contact_urgence_telephone' => ['nullable', 'string', 'max:160'],

            // Social et bancaire
            'numero_cnss' => ['nullable', 'string', 'max:240'],
            'date_immatriculation_cnss' => ['nullable', 'date'],
            'numero_amu' => ['nullable', 'string', 'max:240'],
            'organisme_assurance' => ['nullable', 'string', 'max:255'],
            'banque' => ['nullable', 'string', 'max:255'],
            'compte_bancaire' => ['nullable', 'string', 'max:255'],
            'mode_paiement' => ['nullable', 'string', 'max:200'],

            // Professionnel
            'date_embauche' => ['nullable', 'date'],
            'date_prise_service' => ['nullable', 'date', 'after_or_equal:date_embauche'],
            'type_contrat' => ['nullable', 'string', 'max:160'],
            'reference_contrat' => ['nullable', 'string', 'max:255'],
            'date_contrat' => ['nullable', 'date'],
            'date_fin_contrat' => ['nullable', 'date', 'after_or_equal:date_contrat'],
            'lieu_affectation' => ['nullable', 'string', 'max:255'],
            'statut_emploi' => ['nullable', Rule::in(array_column(StatutEmploi::cases(), 'value'))],

            // Affectation initiale
            'structure_id' => ['nullable', 'exists:structures,id'],
            'poste_id' => ['nullable', 'exists:postes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'prenoms.required' => 'Le prénom est obligatoire.',
            'prenoms.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'sexe.in' => 'Le sexe doit être M ou F.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',

            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format PNG, JPG ou JPEG.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',

            'type_piece.in' => 'Le type de pièce sélectionné est invalide.',

            'email_personnel.email' => 'L\'email personnel doit être une adresse valide.',
            'email_professionnel.email' => 'L\'email professionnel doit être une adresse valide.',

            'gps_latitude.numeric' => 'La latitude doit être un nombre.',
            'gps_latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'gps_longitude.numeric' => 'La longitude doit être un nombre.',
            'gps_longitude.between' => 'La longitude doit être comprise entre -180 et 180.',

            'date_embauche.date' => 'La date d\'embauche doit être une date valide.',
            'date_prise_service.date' => 'La date de prise de service doit être une date valide.',
            'date_prise_service.after_or_equal' => 'La date de prise de service doit être postérieure ou égale à la date d\'embauche.',
            'date_fin_contrat.date' => 'La date de fin de contrat doit être une date valide.',
            'date_fin_contrat.after_or_equal' => 'La date de fin de contrat doit être postérieure ou égale à la date du contrat.',

            'structure_id.exists' => 'La structure sélectionnée n\'existe pas.',
            'poste_id.exists' => 'Le poste sélectionné n\'existe pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'prenoms' => 'prénom',
            'sexe' => 'sexe',
            'date_naissance' => 'date de naissance',
            'lieu_naissance' => 'lieu de naissance',
            'nationalite' => 'nationalité',
            'photo' => 'photo',
            'type_piece' => 'type de pièce',
            'numero_piece' => 'numéro de pièce',
            'date_expiration_piece' => 'date d\'expiration de la pièce',
            'telephone_principal' => 'téléphone principal',
            'telephone_secondaire' => 'téléphone secondaire',
            'email_personnel' => 'email personnel',
            'email_professionnel' => 'email professionnel',
            'adresse' => 'adresse',
            'ville' => 'ville',
            'pays_residence' => 'pays de résidence',
            'gps_latitude' => 'latitude GPS',
            'gps_longitude' => 'longitude GPS',
            'situation_matrimoniale' => 'situation matrimoniale',
            'contact_urgence_nom' => 'nom du contact d\'urgence',
            'contact_urgence_lien' => 'lien du contact d\'urgence',
            'contact_urgence_telephone' => 'téléphone du contact d\'urgence',
            'numero_cnss' => 'numéro CNSS',
            'date_immatriculation_cnss' => 'date d\'immatriculation CNSS',
            'numero_amu' => 'numéro AMU',
            'organisme_assurance' => 'organisme d\'assurance',
            'banque' => 'banque',
            'compte_bancaire' => 'compte bancaire',
            'mode_paiement' => 'mode de paiement',
            'date_embauche' => 'date d\'embauche',
            'date_prise_service' => 'date de prise de service',
            'type_contrat' => 'type de contrat',
            'reference_contrat' => 'référence du contrat',
            'date_contrat' => 'date du contrat',
            'date_fin_contrat' => 'date de fin de contrat',
            'lieu_affectation' => 'lieu d\'affectation',
            'statut_emploi' => 'statut d\'emploi',
            'structure_id' => 'structure',
            'poste_id' => 'poste',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Nettoyer les chaînes vides → null
        $this->merge(array_map(
            fn ($v) => $v === '' ? null : $v,
            $this->except(['_token', '_method'])
        ));
    }
}