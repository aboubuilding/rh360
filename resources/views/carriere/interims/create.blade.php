@extends('layouts.app')

@section('title', 'Nouvel intérim')
@section('page_title', 'Nouvel intérim')
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-interim" method="POST" action="{{ route('carriere.interims.store') }}">
            @csrf
            <input type="hidden" name="type_mouvement" value="interim">

            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salarié" type="select" required
                             :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_effet" label="Date de début" type="date" required
                             :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_fin_prevue" label="Date de fin prévue" type="date" required
                             :value="now()->addMonths(3)->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="structure_cible_id" label="Structure (poste assuré)" type="select"
                             :options="[null => '—'] + $structures->pluck('nom', 'id')->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="poste_cible_id" label="Poste assuré" type="select"
                             :options="[null => '—'] + $postes->pluck('intitule', 'id')->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="lieu_affectation_cible" label="Lieu d'affectation" />
                </div>
                <div class="col-md-12">
                    <x-field name="motif" label="Motif de l'intérim" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Créer l'intérim
                </button>
                <a href="{{ route('carriere.interims.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-interim').on('submit', function (e) {
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
            window.showToast(r.message || 'Intérim créé.');
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