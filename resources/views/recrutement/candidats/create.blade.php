@extends('layouts.app')

@section('title', 'Nouveau candidat')
@section('page_title', 'Nouveau candidat')
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('recrutement.candidats.index') }}">Candidats</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-candidat" method="POST" action="{{ route('recrutement.candidats.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="besoin_id" label="Besoin associé" type="select"
                             :value="request('besoin_id')"
                             :options="[null => '— Aucun —'] + $besoins->pluck('intitule_poste', 'id')->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="source" label="Source" type="select" required
                             :options="$sources" :value="'spontanee'" />
                </div>

                <div class="col-md-6">
                    <x-field name="nom" label="Nom" required />
                </div>
                <div class="col-md-6">
                    <x-field name="prenoms" label="Prénoms" required />
                </div>

                <div class="col-md-6">
                    <x-field name="email" label="Email" type="email" />
                </div>
                <div class="col-md-6">
                    <x-field name="telephone" label="Téléphone" />
                </div>

                <div class="col-md-12">
                    <x-field name="observations" label="Observations" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('recrutement.candidats.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-candidat').on('submit', function (e) {
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
            window.showToast(r.message || 'Créé.');
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