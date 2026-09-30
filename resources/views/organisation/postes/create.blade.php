@extends('layouts.app')

@section('title', 'Nouveau poste')
@section('page_title', 'Nouveau poste')
@section('page_icon', 'fa-id-badge')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('organisation.postes.index') }}">Postes</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-poste" method="POST" action="{{ route('organisation.postes.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-12">
                    <x-field name="structure_id" label="Structure de rattachement" type="select" required
                             :options="$structures->pluck('nom', 'id')->all()" />
                </div>
                <div class="col-md-4">
                    <x-field name="code" label="Code" required />
                </div>
                <div class="col-md-8">
                    <x-field name="intitule" label="Intitulé du poste" required />
                </div>
                <div class="col-md-6">
                    <x-field name="categorie" label="Catégorie" help="Ex. A, B, C..." />
                </div>
                <div class="col-md-6">
                    <x-field name="effectif_cible" label="Effectif cible" type="number" />
                </div>
                <div class="col-md-12">
                    <x-field name="actif" label="Actif" type="checkbox" :value="true" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('organisation.postes.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-poste').on('submit', function (e) {
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
            setTimeout(() => { window.location.href = "{{ route('organisation.postes.index') }}"; }, 600);
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