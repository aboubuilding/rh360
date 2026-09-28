@extends('layouts.app')

@section('title', 'Reprendre une situation')
@section('page_title', 'Reprendre une situation antérieure')
@section('page_icon', 'fa-history')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.situations.index') }}">Situations</a></li>
    <li><a href="{{ route('carriere.situations.show', $salarie) }}">{{ $salarie->nom_complet }}</a></li>
    <li>Reprise</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Cette opération reconstruit une situation de carrière antérieure. Elle est traçable
    et conserve les valeurs précédentes.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-reprise" method="POST"
              action="{{ route('carriere.situations.reprendre.store', $salarie) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="position_classification_id" label="Position de classification" type="select" required
                             :options="$positions->mapWithKeys(fn($p) => [$p->id => $p->code.' — '.$p->libelleComplet()])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="position_ouverture_id" label="Position d'ouverture (optionnel)" type="select"
                             :options="[null => '—'] + $positions->mapWithKeys(fn($p) => [$p->id => $p->code.' — '.$p->libelleComplet()])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_reference_ouverture" label="Date de référence d'ouverture" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_effet_categorie" label="Effet catégorie" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_effet_classe" label="Effet classe" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_effet_echelon" label="Effet échelon" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_reference_avancement" label="Réf. avancement" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="reference_acte" label="Référence de l'acte" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_acte" label="Date de l'acte" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="justificatif" label="Justificatif" type="file"
                             help="PDF, PNG, JPG — 8 Mo max." />
                </div>
                <div class="col-md-12">
                    <x-field name="observations" label="Observations" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer la reprise
                </button>
                <a href="{{ route('carriere.situations.show', $salarie) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-reprise').on('submit', function (e) {
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
            window.showToast(r.message || 'Situation reconstituée.');
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