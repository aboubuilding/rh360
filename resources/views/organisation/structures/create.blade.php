@extends('layouts.app')

@section('title', 'Nouvelle structure')
@section('page_title', 'Nouvelle structure')
@section('page_icon', 'fa-sitemap')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('organisation.structures.index') }}">Structures</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-structure" method="POST" action="{{ route('organisation.structures.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="type_structure_id" label="Type de structure" type="select" required
                             :options="$types->pluck('nom', 'id')->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="parent_id" label="Structure parente" type="select"
                             :options="[null => '— Aucune —'] + $parents->pluck('nom', 'id')->all()" />
                </div>
                <div class="col-md-4">
                    <x-field name="code" label="Code" required />
                </div>
                <div class="col-md-8">
                    <x-field name="nom" label="Nom" required />
                </div>
                <div class="col-md-6">
                    <x-field name="localisation" label="Localisation" />
                </div>
                <div class="col-md-6">
                    <x-field name="centre_cout" label="Centre de coût" />
                </div>
                <div class="col-md-12">
                    <x-field name="actif" label="Actif" type="checkbox" :value="true" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('organisation.structures.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-structure').on('submit', function (e) {
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
            window.showToast(r.message || 'Créée.');
            setTimeout(() => { window.location.href = "{{ route('organisation.structures.index') }}"; }, 600);
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