@extends('layouts.app')

@section('title', 'Programmer une visite médicale')
@section('page_title', 'Programmer une visite médicale')
@section('page_icon', 'fa-stethoscope')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.visites.index') }}">Visites médicales</a></li>
    <li>Programmer</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-lock"></i>
    Les informations médicales sont confidentielles. Seuls les utilisateurs disposant de la
    permission <code>sensitive.social_health</code> peuvent accéder à cette page.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-visite" method="POST" action="{{ route('sst.visites.store') }}">
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
                    <x-field name="type_visite" label="Type de visite" type="select" required
                             :options="$types" />
                </div>

                <div class="col-md-6">
                    <x-field name="date_prevue" label="Date prévue" type="date" required
                             :value="now()->addDays(7)->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="prestataire" label="Prestataire / médecin" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Programmer
                </button>
                <a href="{{ route('sst.visites.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-visite').on('submit', function (e) {
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
            window.showToast(r.message || 'Visite programmée.');
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