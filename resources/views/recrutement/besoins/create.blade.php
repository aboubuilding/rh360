@extends('layouts.app')

@section('title', 'Nouveau besoin de recrutement')
@section('page_title', 'Nouveau besoin de recrutement')
@section('page_icon', 'fa-user-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('recrutement.besoins.index') }}">Besoins</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-besoin-rec" method="POST" action="{{ route('recrutement.besoins.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="intitule_poste" label="Intitulé du poste" required />
                </div>
                <div class="col-md-3">
                    <x-field name="nombre_postes" label="Nombre de postes" type="number" min="1" required :value="1" />
                </div>
                <div class="col-md-3">
                    <x-field name="type_contrat" label="Type de contrat" required :value="'CDI'"
                             help="CDI, CDD, Stage, Intérim..." />
                </div>

                <div class="col-md-6">
                    <x-field name="departement" label="Département / structure" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_cible" label="Date cible" type="date"
                             :value="now()->addMonth()->format('Y-m-d')" />
                </div>

                <div class="col-md-12">
                    <x-field name="motif" label="Motif" type="textarea"
                             help="Remplacement, création de poste, surcroît d'activité..." />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('recrutement.besoins.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-besoin-rec').on('submit', function (e) {
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