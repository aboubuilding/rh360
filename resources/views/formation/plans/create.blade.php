@extends('layouts.app')

@section('title', 'Nouveau plan de formation')
@section('page_title', 'Nouveau plan de formation')
@section('page_icon', 'fa-calendar-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('formation.plans.index') }}">Plans</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-plan" method="POST" action="{{ route('formation.plans.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <x-field name="intitule" label="Intitulé" required
                             :value="'Plan de formation ' . now()->year" />
                </div>
                <div class="col-md-4">
                    <x-field name="annee" label="Année" type="number" required :value="now()->year" />
                </div>

                <div class="col-md-6">
                    <x-field name="debut_prevu" label="Début prévu" type="date"
                             :value="now()->startOfYear()->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="montant_budget" label="Budget (FCFA)" type="number" step="0.01" required
                             :value="0" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('formation.plans.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-plan').on('submit', function (e) {
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
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Plan créé.');
            setTimeout(() => { window.location.href = r.redirect; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else { window.showToast('Erreur.', 'error'); }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush