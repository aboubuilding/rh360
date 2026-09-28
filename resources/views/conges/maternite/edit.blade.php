@extends('layouts.app')

@section('title', 'Nouvelle déclaration de maternité')
@section('page_title', 'Nouvelle déclaration de maternité')
@section('page_icon', 'fa-baby')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.maternite.index') }}">Maternité</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-lock"></i> Ces informations sont strictement confidentielles.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-maternite" method="POST"
              action="{{ route('conges.maternite.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salariée" type="select" required
                             :options="$salariees->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_declaration" label="Date de déclaration" type="date"
                             :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_prevue_accouchement" label="Date prévue d'accouchement" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_debut_conge" label="Date de début du congé" type="date" />
                </div>

                <div class="col-md-4">
                    <x-field name="date_consultation_1" label="Consultation prénatale 1" type="date" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_consultation_2" label="Consultation prénatale 2" type="date" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_consultation_3" label="Consultation prénatale 3" type="date" />
                </div>

                <div class="col-md-12">
                    <x-field name="risque_poste_identifie" label="Risque au poste identifié" type="checkbox" />
                </div>
                <div class="col-md-12">
                    <x-field name="amenagement_temporaire" label="Aménagement temporaire" type="textarea" />
                </div>

                <div class="col-md-6">
                    <x-field name="certificat_medical" label="Certificat médical" type="file"
                             help="PDF, PNG, JPG — 8 Mo max." />
                </div>
                <div class="col-md-6">
                    <x-field name="date_reprise_effective" label="Date de reprise (si connue)" type="date" />
                </div>

                <div class="col-md-6">
                    <x-field name="reference_acte" label="Référence acte" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_acte" label="Date acte" type="date" />
                </div>

                <div class="col-md-12">
                    <x-field name="observations" label="Observations" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('conges.maternite.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-maternite').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Dossier enregistré.');
            setTimeout(() => { window.location.href = r.redirect; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush