@extends('layouts.app')

@section('title', 'Nouvelle dotation EPI')
@section('page_title', 'Nouvelle dotation d\'équipement de protection')
@section('page_icon', 'fa-shield-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.epi.index') }}">EPI</a></li>
    <li>Nouvelle dotation</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-epi" method="POST" action="{{ route('sst.epi.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salarié" type="select" required
                             :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="risque_id" label="Risque couvert" type="select"
                             :options="[null => '—'] + $risques->pluck('intitule', 'id')->all()" />
                </div>

                <div class="col-md-4">
                    <x-field name="categorie" label="Catégorie" type="select" required
                             :options="$categories" />
                </div>
                <div class="col-md-8">
                    <x-field name="intitule" label="Intitulé de l'équipement" required
                             help="Ex. Casque de chantier, Gants nitrile..." />
                </div>

                <div class="col-md-3">
                    <x-field name="quantite" label="Quantité" type="number" min="1" required :value="1" />
                </div>
                <div class="col-md-3">
                    <x-field name="unite" label="Unité" required :value="'unité'" />
                </div>
                <div class="col-md-3">
                    <x-field name="numero_serie" label="Numéro de série" />
                </div>
                <div class="col-md-3">
                    <x-field name="taille" label="Taille" />
                </div>

                <div class="col-md-4">
                    <x-field name="date_remise" label="Date de remise" type="date" required
                             :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_expiration" label="Date d'expiration" type="date" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_verification" label="Prochaine vérification" type="date" />
                </div>

                <div class="col-md-6">
                    <x-field name="emetteur" label="Émetteur" required
                             :value="auth()->user()->nom_complet" />
                </div>
                <div class="col-md-6">
                    <x-field name="reference_recu" label="Référence du reçu" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer la dotation
                </button>
                <a href="{{ route('sst.epi.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-epi').on('submit', function (e) {
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
            window.showToast(r.message || 'Dotation enregistrée.');
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