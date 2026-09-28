@extends('layouts.app')

@section('title', 'Nouveau besoin de formation')
@section('page_title', 'Nouveau besoin de formation')
@section('page_icon', 'fa-clipboard-list')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('formation.besoins.index') }}">Besoins</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-besoin" method="POST" action="{{ route('formation.besoins.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salarié concerné" type="select"
                             :options="[null => '— Besoin global —'] + $salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="intitule" label="Intitulé du besoin" required />
                </div>

                <div class="col-md-6">
                    <x-field name="priorite" label="Priorité" type="select" required
                             :options="$priorites" :value="'normale'" />
                </div>
                <div class="col-md-6">
                    <x-field name="annee_cible" label="Année cible" type="number" required
                             :value="now()->year" />
                </div>

                <div class="col-md-12">
                    <x-field name="motif" label="Motif / justification" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('formation.besoins.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-besoin').on('submit', function (e) {
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
        .done(function (r) { window.showToastThenReload(r.message || 'Créé.'); })
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