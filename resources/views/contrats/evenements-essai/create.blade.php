@extends('layouts.app')

@section('title', 'Déclarer un événement d\'essai')
@section('page_title', 'Déclarer un événement d\'essai')
@section('page_icon', 'fa-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('contrats.contrats.index') }}">Contrats</a></li>
    <li><a href="{{ route('contrats.contrats.show', $contrat) }}">{{ $contrat->reference }}</a></li>
    <li>Déclarer un événement</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Contrat <strong>{{ $contrat->reference }}</strong> —
    Salarié <strong>{{ $contrat->salarie?->nom_complet }}</strong>.<br>
    Fin d'essai ajustée : <strong>{{ $calculEssai['date_fin_ajustee'] ?? '—' }}</strong>.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-evenement" method="POST"
              action="{{ route('contrats.contrats.evenements-essai.store', $contrat) }}">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-field name="nature" label="Nature de l'événement" type="select" required
                             :options="$natures" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_debut" label="Date de début" type="date" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_fin" label="Date de fin" type="date" />
                </div>
                <div class="col-md-3">
                    <x-field name="duree_jours" label="Durée (jours)" type="number" />
                </div>
                <div class="col-md-12">
                    <x-field name="commentaire" label="Commentaire" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Déclarer
                </button>
                <a href="{{ route('contrats.contrats.show', $contrat) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-evenement').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Événement déclaré.');
            setTimeout(() => { window.location.href = "{{ route('contrats.contrats.show', $contrat) }}"; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
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