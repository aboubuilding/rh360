@extends('layouts.app')

@section('title', 'Nouvel acte d\'heures supplémentaires')
@section('page_title', 'Nouvel acte d\'heures supplémentaires')
@section('page_icon', 'fa-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.heures-supp.index') }}">Heures supplémentaires</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-hs" method="POST" action="{{ route('paie.heures-supp.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-4">
                    <x-field name="salarie_id" label="Salarié" type="select" required
                             :value="$salarie?->id"
                             :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-4">
                    <x-field name="reference" label="Référence" required
                             :value="'HS-' . now()->format('Ymd') . '-'" />
                </div>
                <div class="col-md-4">
                    <x-field name="periode_paiement_id" label="Période de paiement" type="select" required
                             :options="$periodes->mapWithKeys(fn($p) => [$p->id => $p->libelle])->all()" />
                </div>

                <div class="col-md-6">
                    <x-field name="debut_travail" label="Début de la période" type="date" required />
                </div>
                <div class="col-md-6">
                    <x-field name="fin_travail" label="Fin de la période" type="date" required />
                </div>
            </div>

            <hr>
            <h6 class="text-muted mb-3">Heures par taux</h6>

            <div class="row">
                <div class="col-md-3">
                    <x-field name="heures_hs20" label="HS 20 %" type="number" step="0.5" :value="0" />
                </div>
                <div class="col-md-3">
                    <x-field name="heures_hs40" label="HS 40 %" type="number" step="0.5" :value="0" />
                </div>
                <div class="col-md-3">
                    <x-field name="heures_hs65_jour" label="HS 65 % jour" type="number" step="0.5" :value="0" />
                </div>
                <div class="col-md-3">
                    <x-field name="heures_hs65_nuit" label="HS 65 % nuit" type="number" step="0.5" :value="0" />
                </div>
                <div class="col-md-3">
                    <x-field name="heures_hs100" label="HS 100 %" type="number" step="0.5" :value="0" />
                </div>
            </div>

            <div class="col-md-12">
                <x-field name="motif" label="Motif" type="textarea" />
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('paie.heures-supp.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-hs').on('submit', function (e) {
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
            window.showToast(r.message || 'Enregistré.');
            setTimeout(() => { window.location.href = r.redirect; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush