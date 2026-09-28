@extends('layouts.app')

@section('title', 'Nouvelle habilitation')
@section('page_title', 'Nouvelle habilitation')
@section('page_icon', 'fa-certificate')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.habilitations.index') }}">Habilitations</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-habilitation" method="POST" action="{{ route('sst.habilitations.store') }}">
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
                    <x-field name="risque_id" label="Risque lié" type="select"
                             :options="[null => '—'] + $risques->pluck('intitule', 'id')->all()" />
                </div>

                <div class="col-md-4">
                    <x-field name="categorie" label="Catégorie" required
                             help="Ex. Électrique, Travaux en hauteur..." />
                </div>
                <div class="col-md-8">
                    <x-field name="intitule" label="Intitulé de l'habilitation" required />
                </div>

                <div class="col-md-12">
                    <x-field name="portee" label="Portée / périmètre" type="textarea" required />
                </div>

                <div class="col-md-6">
                    <x-field name="emetteur" label="Émetteur" />
                </div>
                <div class="col-md-6">
                    <x-field name="reference_decision" label="Référence de la décision" />
                </div>

                <div class="col-md-6">
                    <x-field name="reference_formation" label="Référence de la formation" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_decision" label="Date de la décision" type="date" />
                </div>

                <div class="col-md-4">
                    <x-field name="date_debut" label="Date de début" type="date" required
                             :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_fin" label="Date de fin" type="date" />
                </div>
                <div class="col-md-4">
                    <x-field name="date_revue" label="Date de revue" type="date" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('sst.habilitations.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-habilitation').on('submit', function (e) {
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
            window.showToast(r.message || 'Habilitation enregistrée.');
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