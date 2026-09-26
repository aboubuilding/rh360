@extends('layouts.app')

@section('title', 'Modifier ' . $salarie->nom_complet)
@section('page_title', 'Modifier la fiche salarié')
@section('page_icon', 'fa-user-edit')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li><a href="{{ route('personnel.salaries.show', $salarie) }}">{{ $salarie->nom_complet }}</a></li>
    <li>Modifier</li>
@endsection

@section('contenu')
<form id="form-salarie" method="POST"
      action="{{ route('personnel.salaries.update', $salarie) }}"
      enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Section Identité --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Identité</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <x-field name="nom" label="Nom" required :value="$salarie->nom" />
                </div>
                <div class="col-md-6">
                    <x-field name="prenoms" label="Prénoms" required :value="$salarie->prenoms" />
                </div>
                <div class="col-md-4">
                    <x-field name="sexe" label="Sexe" type="select" :value="$salarie->sexe"
                             :options="['' => '—', 'M' => 'Masculin', 'F' => 'Féminin']" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_naissance" label="Date de naissance" type="date"
                             :value="$salarie->date_naissance?->format('Y-m-d')" />
                </div>
                <div class="col-md-4">
                    <x-field name="lieu_naissance" label="Lieu de naissance"
                             :value="$salarie->lieu_naissance" />
                </div>
                <div class="col-md-6">
                    <x-field name="nationalite" label="Nationalité" :value="$salarie->nationalite" />
                </div>
                <div class="col-md-6">
                    <x-field name="photo" label="Photo (laisser vide pour conserver)" type="file" />
                </div>
                <div class="col-md-4">
                    <x-field name="type_piece" label="Type de pièce" type="select"
                             :value="$salarie->type_piece"
                             :options="['' => '—'] + \App\Domain\Personnel\Enums\TypePieceIdentite::options()" />
                </div>
                <div class="col-md-4">
                    <x-field name="numero_piece" label="Numéro de pièce"
                             :value="$salarie->numero_piece" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_expiration_piece" label="Expiration pièce" type="date"
                             :value="$salarie->date_expiration_piece?->format('Y-m-d')" />
                </div>
            </div>
        </div>
    </div>

    {{-- Section Coordonnées --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Coordonnées</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <x-field name="telephone_principal" label="Téléphone principal"
                             :value="$salarie->telephone_principal" />
                </div>
                <div class="col-md-6">
                    <x-field name="telephone_secondaire" label="Téléphone secondaire"
                             :value="$salarie->telephone_secondaire" />
                </div>
                <div class="col-md-6">
                    <x-field name="email_personnel" label="Email personnel" type="email"
                             :value="$salarie->email_personnel" />
                </div>
                <div class="col-md-6">
                    <x-field name="email_professionnel" label="Email professionnel" type="email"
                             :value="$salarie->email_professionnel" />
                </div>
                <div class="col-md-12">
                    <x-field name="adresse" label="Adresse" type="textarea"
                             :value="$salarie->adresse" />
                </div>
                <div class="col-md-6">
                    <x-field name="ville" label="Ville" :value="$salarie->ville" />
                </div>
                <div class="col-md-6">
                    <x-field name="pays_residence" label="Pays de résidence"
                             :value="$salarie->pays_residence" />
                </div>
                @can('permission', 'sensitive.gps')
                    <div class="col-md-6">
                        <x-field name="gps_latitude" label="Latitude GPS" type="number"
                                 :value="$salarie->gps_latitude" />
                    </div>
                    <div class="col-md-6">
                        <x-field name="gps_longitude" label="Longitude GPS" type="number"
                                 :value="$salarie->gps_longitude" />
                    </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- Section Famille --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Famille & urgence</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <x-field name="situation_matrimoniale" label="Situation matrimoniale" type="select"
                             :value="$salarie->situation_matrimoniale"
                             :options="[
                                 '' => '—',
                                 'Célibataire' => 'Célibataire',
                                 'Marié(e)' => 'Marié(e)',
                                 'Divorcé(e)' => 'Divorcé(e)',
                                 'Veuf(ve)' => 'Veuf(ve)',
                             ]" />
                </div>
                <div class="col-md-4">
                    <x-field name="contact_urgence_nom" label="Contact d'urgence (nom)"
                             :value="$salarie->contact_urgence_nom" />
                </div>
                <div class="col-md-4">
                    <x-field name="contact_urgence_telephone" label="Contact d'urgence (téléphone)"
                             :value="$salarie->contact_urgence_telephone" />
                </div>
                <div class="col-md-12">
                    <x-field name="contact_urgence_lien" label="Lien avec le contact"
                             :value="$salarie->contact_urgence_lien" />
                </div>
            </div>
        </div>
    </div>

    {{-- Section Social (protégée) --}}
    @can('permission', 'sensitive.social_health')
        <div class="card mb-3">
            <div class="card-header"><strong>Social & santé</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <x-field name="numero_cnss" label="N° CNSS" :value="$salarie->numero_cnss" />
                    </div>
                    <div class="col-md-4">
                        <x-field name="date_immatriculation_cnss" label="Immatriculation CNSS" type="date"
                                 :value="$salarie->date_immatriculation_cnss?->format('Y-m-d')" />
                    </div>
                    <div class="col-md-4">
                        <x-field name="numero_amu" label="N° AMU" :value="$salarie->numero_amu" />
                    </div>
                    <div class="col-md-12">
                        <x-field name="organisme_assurance" label="Organisme d'assurance"
                                 :value="$salarie->organisme_assurance" />
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Section Bancaire (protégée) --}}
    @can('permission', 'sensitive.banking')
        <div class="card mb-3">
            <div class="card-header"><strong>Bancaire</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <x-field name="banque" label="Banque" :value="$salarie->banque" />
                    </div>
                    <div class="col-md-4">
                        <x-field name="compte_bancaire" label="Compte bancaire"
                                 :value="$salarie->compte_bancaire" />
                    </div>
                    <div class="col-md-4">
                        <x-field name="mode_paiement" label="Mode de paiement" :value="$salarie->mode_paiement" />
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Section Professionnel --}}
    <div class="card mb-3">
        <div class="card-header"><strong>Situation professionnelle</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <x-field name="date_embauche" label="Date d'embauche" type="date"
                             :value="$salarie->date_embauche?->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_prise_service" label="Date de prise de service" type="date"
                             :value="$salarie->date_prise_service?->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="type_contrat" label="Type de contrat"
                             :value="$salarie->type_contrat" />
                </div>
                <div class="col-md-6">
                    <x-field name="reference_contrat" label="Référence du contrat"
                             :value="$salarie->reference_contrat" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_contrat" label="Date du contrat" type="date"
                             :value="$salarie->date_contrat?->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_fin_contrat" label="Date de fin de contrat" type="date"
                             :value="$salarie->date_fin_contrat?->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="lieu_affectation" label="Lieu d'affectation"
                             :value="$salarie->lieu_affectation" />
                </div>
                <div class="col-md-6">
                    <x-field name="statut_emploi" label="Statut d'emploi" type="select"
                             :value="$salarie->statut_emploi?->value"
                             :options="\App\Domain\Personnel\Enums\StatutEmploi::options()" />
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <a href="{{ route('personnel.salaries.show', $salarie) }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    $('#form-salarie').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Fiche mise à jour.');
            setTimeout(() => { window.location.href = "{{ route('personnel.salaries.show', $salarie) }}"; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur lors de l\'enregistrement.', 'error');
            }
        })
        .always(function () {
            $btn.prop('disabled', false).html(texte);
        });
    });
});
</script>
@endpush