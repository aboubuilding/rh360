@extends('layouts.app')

@section('title', 'Constater une absence')
@section('page_title', 'Constater une absence')
@section('page_icon', 'fa-user-slash')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.absences.index') }}">Absences</a></li>
    <li>Constater</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-absence" method="POST" action="{{ route('conges.absences.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    @if($salarie)
                        <div class="alert alert-info py-2">
                            Salarié : <strong>{{ $salarie->nom_complet }}</strong>
                            <input type="hidden" name="salarie_id" value="{{ $salarie->id }}">
                        </div>
                    @else
                        <x-field name="salarie_id" label="Salarié" type="select" required
                                 :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                    @endif
                </div>
                <div class="col-md-6">
                    <x-field name="type_conge_id" label="Type" type="select" required
                             :options="$types->pluck('nom', 'id')->all()" />
                </div>

                <div class="col-md-6">
                    <x-field name="debut_le" label="Début" type="datetime-local" required
                             :value="now()->format('Y-m-d\TH:i')" />
                </div>
                <div class="col-md-6">
                    <x-field name="fin_le" label="Fin" type="datetime-local"
                             help="Laisser vide si absence ouverte." />
                </div>

                <div class="col-md-6">
                    <x-field name="duree_heures" label="Durée (heures)" type="number" step="0.5" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_limite_justification" label="Date limite de justification" type="date" />
                </div>

                <div class="col-md-12">
                    <x-field name="motif" label="Motif" type="textarea" />
                </div>
                <div class="col-md-12">
                    <x-field name="justification" label="Justification (optionnel)" type="textarea" />
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('conges.absences.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-absence').on('submit', function (e) {
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
            window.showToast(r.message || 'Absence enregistrée.');
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