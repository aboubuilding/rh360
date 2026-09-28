@extends('layouts.app')

@section('title', 'Situation de ' . $salarie->nom_complet)
@section('page_title', 'Situation de carrière')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.situations.index') }}">Situations</a></li>
    <li>{{ $salarie->nom_complet }}</li>
@endsection

@section('page_actions')
    @can('permission', 'carriere.manage')
        <a href="{{ route('carriere.situations.reprendre', $salarie) }}" class="btn btn-secondary">
            <i class="fas fa-history"></i> Reprendre une situation
        </a>
    @endcan
    <a href="{{ route('personnel.salaries.show', $salarie) }}" class="btn btn-outline-secondary">
        <i class="fas fa-user"></i> Fiche salarié
    </a>
@endsection

@section('contenu')
<div class="row">
    <div class="col-md-6">
        @include('carriere.situations.partials._situation-courante')
    </div>
    <div class="col-md-6">
        @include('carriere.situations.partials._chronologie')
    </div>
</div>

@include('carriere.situations.partials._modal-confirmer')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-confirmer-fiabilite', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirmer')).show();
    });

    $('#form-confirmer').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-confirmer')).hide();
            window.showToastThenReload(r.message || 'Fiabilité confirmée.');
        })
        .fail(function () { window.showToast('Erreur.', 'error'); })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush