@extends('layouts.app')

@section('title', 'Ouvrir une période')
@section('page_title', 'Ouvrir une période de paie')
@section('page_icon', 'fa-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.periodes.index') }}">Périodes</a></li>
    <li>Ouvrir</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-periode" method="POST" action="{{ route('paie.periodes.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-field name="annee" label="Année" type="number" required
                             :value="now()->year" />
                </div>
                <div class="col-md-4">
                    <x-field name="mois" label="Mois" type="select" required
                             :value="now()->month"
                             :options="[
                                 1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                             ]" />
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i>
                Une période ne peut être ouverte qu'une seule fois par mois et par entreprise.
                Elle reste modifiable jusqu'à sa validation définitive.
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Ouvrir la période
                </button>
                <a href="{{ route('paie.periodes.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-periode').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ouverture...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Période ouverte.');
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
            } else if (xhr.responseJSON?.message) {
                window.showToast(xhr.responseJSON.message, 'error');
            } else {
                window.showToast('Erreur d\'ouverture.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush