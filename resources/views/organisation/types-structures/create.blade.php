@extends('layouts.app')

@section('title', 'Nouveau type de structure')
@section('page_title', 'Nouveau type de structure')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('organisation.types-structures.index') }}">Types de structures</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-type-structure" method="POST" action="{{ route('organisation.types-structures.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-field name="code" label="Code" required help="Ex. DG, DIR, DEP..." />
                </div>
                <div class="col-md-8">
                    <x-field name="nom" label="Nom" required />
                </div>
                <div class="col-md-4">
                    <x-field name="ordre" label="Ordre d'affichage" type="number" :value="100" />
                </div>
                <div class="col-md-12">
                    <x-field name="actif" label="Actif" type="checkbox" :value="true" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('organisation.types-structures.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-type-structure').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Créé.');
            setTimeout(() => { window.location.href = "{{ route('organisation.types-structures.index') }}"; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
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