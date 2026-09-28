@extends('layouts.app')

@section('title', 'Nouvelle session de formation')
@section('page_title', 'Nouvelle session de formation')
@section('page_icon', 'fa-chalkboard-teacher')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('formation.sessions.index') }}">Sessions</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-session" method="POST" action="{{ route('formation.sessions.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="plan_formation_id" label="Plan de formation" type="select"
                             :options="[null => '— Aucun —'] + $plans->mapWithKeys(fn($p) => [$p->id => $p->intitule.' ('.$p->annee.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="intitule" label="Intitulé" required />
                </div>

                <div class="col-md-6">
                    <x-field name="prestataire" label="Prestataire" />
                </div>
                <div class="col-md-6">
                    <x-field name="localisation" label="Lieu" />
                </div>

                <div class="col-md-3">
                    <x-field name="date_debut" label="Début" type="date" required
                             :value="now()->addDays(15)->format('Y-m-d')" />
                </div>
                <div class="col-md-3">
                    <x-field name="date_fin" label="Fin" type="date" required
                             :value="now()->addDays(17)->format('Y-m-d')" />
                </div>
                <div class="col-md-3">
                    <x-field name="duree_heures" label="Durée (h)" type="number" step="0.5" required :value="8" />
                </div>
                <div class="col-md-3">
                    <x-field name="cout_reel" label="Coût (FCFA)" type="number" step="0.01" :value="0" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                <a href="{{ route('formation.sessions.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-session').on('submit', function (e) {
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
            window.showToast(r.message || 'Session créée.');
            setTimeout(() => { window.location.href = r.redirect; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else if (xhr.responseJSON?.message) {
                window.showToast(xhr.responseJSON.message, 'error');
            } else { window.showToast('Erreur.', 'error'); }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush