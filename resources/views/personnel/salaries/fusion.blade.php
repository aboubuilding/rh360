@extends('layouts.app')

@section('title', 'Fusionner des fiches')
@section('page_title', 'Fusionner deux fiches salarié')
@section('page_icon', 'fa-code-branch')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li><a href="{{ route('personnel.salaries.show', $source) }}">{{ $source->nom_complet }}</a></li>
    <li>Fusion</li>
@endsection

@section('contenu')
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Opération sensible.</strong>
    La fiche source sera absorbée dans la fiche cible. Ses membres du foyer et documents
    seront déplacés. La fiche source sera conservée en archive avec la mention « fusionnée ».
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <strong>Fiche source (à absorber)</strong>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ $source->nom_complet }}</strong></p>
                <p class="mb-1 text-muted small">Matricule : {{ $source->matricule }}</p>
                @if($source->date_embauche)
                    <p class="mb-0 text-muted small">Embauche : {{ $source->date_embauche->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-2 text-center d-flex align-items-center justify-content-center">
        <i class="fas fa-arrow-right fa-3x text-muted"></i>
    </div>

    <div class="col-md-5">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <strong>Fiche cible (à conserver)</strong>
            </div>
            <div class="card-body">
                <p class="text-muted small">Sélectionnez la fiche à conserver :</p>
                <select id="cible_id" class="form-select">
                    <option value="">— Choisir —</option>
                    @foreach($candidats as $c)
                        <option value="{{ $c->id }}" data-nom="{{ $c->nom_complet }}">
                            {{ $c->nom_complet }} ({{ $c->matricule }})
                        </option>
                    @endforeach
                </select>
                <div id="apercu-cible" class="mt-2 small text-muted"></div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('personnel.salaries.fusion.store', $source) }}" id="form-fusion">
    @csrf
    <input type="hidden" name="cible_id" id="cible_id_hidden">

    <div class="card mt-3">
        <div class="card-body">
            <x-field name="motif" label="Motif de la fusion" type="textarea" required
                     help="Ex. Doublon créé lors d'un import." />
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-danger" id="btn-fusionner" disabled>
            <i class="fas fa-code-branch"></i> Fusionner
        </button>
        <a href="{{ route('personnel.salaries.show', $source) }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    $('#cible_id').on('change', function () {
        const val = $(this).val();
        const nom = $(this).find(':selected').data('nom');
        $('#cible_id_hidden').val(val);
        $('#apercu-cible').html(val ? '<i class="fas fa-check text-success"></i> Conserver : <strong>' + nom + '</strong>' : '');
        $('#btn-fusionner').prop('disabled', !val);
    });

    $('#form-fusion').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#btn-fusionner');
        const texte = $btn.html();

        Swal.fire({
            icon: 'warning',
            title: 'Confirmer la fusion ?',
            text: 'Cette opération est irréversible.',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, fusionner',
            cancelButtonText: 'Annuler'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Fusion...');
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) {
                window.showToast(r.message || 'Fiches fusionnées.');
                setTimeout(() => { window.location.href = r.redirect || "{{ route('personnel.salaries.index') }}"; }, 800);
            })
            .fail(function (xhr) {
                window.showToast('Erreur lors de la fusion.', 'error');
            })
            .always(function () {
                $btn.prop('disabled', false).html(texte);
            });
        });
    });
});
</script>
@endpush