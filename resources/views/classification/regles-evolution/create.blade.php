@extends('layouts.app')

@section('title', 'Nouveau référentiel')
@section('page_title', 'Nouveau référentiel')
@section('page_icon', 'fa-book')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('classification.referentiels.index') }}">Référentiels</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-referentiel" method="POST"
              action="{{ route('classification.referentiels.store') }}">
            @csrf
            @include('classification.referentiels._form')

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('classification.referentiels.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    const URL_STORE = "{{ route('classification.referentiels.store') }}";

    $('#form-referentiel').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texteBtn = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: URL_STORE,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (response) {
            window.showToast(response.message || 'Référentiel créé.');
            setTimeout(() => { window.location.href = "{{ route('classification.referentiels.index') }}"; }, 600);
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
            $btn.prop('disabled', false).html(texteBtn);
        });
    });
});
</script>
@endpush