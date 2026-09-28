@extends('layouts.app')

@section('title', 'Paramètres des contrats')
@section('page_title', 'Paramètres des contrats')
@section('page_icon', 'fa-sliders-h')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('contrats.contrats.index') }}">Contrats</a></li>
    <li>Paramètres</li>
@endsection

@section('contenu')
<form id="form-parametres" method="POST" action="{{ route('contrats.parametres.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header"><strong>Seuils de notification</strong></div>
        <div class="card-body">
            <p class="text-muted small">
                Renseignez les seuils en jours avant l'échéance (ex. 30, 15, 7, 0).
                Chaque seuil déclenche une notification pour les rôles sélectionnés.
            </p>

            <div id="seuils-wrapper" class="d-flex flex-wrap gap-2 align-items-end">
                @foreach($parametres->seuils as $i => $seuil)
                    <div class="input-group" style="width:140px;">
                        <input type="number" name="seuils[]" value="{{ $seuil }}" min="0" max="365"
                               class="form-control">
                        <button type="button" class="btn btn-outline-danger js-supprimer-seuil">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endforeach
                <button type="button" class="btn btn-outline-secondary" id="btn-ajouter-seuil">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Rôles destinataires</strong></div>
        <div class="card-body">
            <p class="text-muted small">Rôles qui reçoivent les notifications d'échéance.</p>
            @php
                $tousRoles = ['rh' => 'Responsable RH', 'drh' => 'DRH', 'manager' => 'Manager', 'direction' => 'Direction générale'];
            @endphp
            <div class="row">
                @foreach($tousRoles as $val => $lib)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $val }}"
                                   id="role-{{ $val }}"
                                   @checked(in_array($val, $parametres->roles, true))>
                            <label class="form-check-label" for="role-{{ $val }}">{{ $lib }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        <a href="{{ route('contrats.contrats.index') }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@push('js')
<script>
$(function () {
    $('#btn-ajouter-seuil').on('click', function () {
        $('#seuils-wrapper').prepend(`
            <div class="input-group mb-0" style="width:140px;">
                <input type="number" name="seuils[]" value="0" min="0" max="365" class="form-control">
                <button type="button" class="btn btn-outline-danger js-supprimer-seuil">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
    });

    $(document).on('click', '.js-supprimer-seuil', function () {
        if ($('#seuils-wrapper input[name="seuils[]"]').length <= 1) {
            window.showToast('Au moins un seuil est requis.', 'error');
            return;
        }
        $(this).closest('.input-group').remove();
    });

    $('#form-parametres').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Paramètres enregistrés.');
            setTimeout(() => { window.location.href = "{{ route('contrats.contrats.index') }}"; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    window.showToast(messages[0], 'error');
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