@extends('layouts.app')

@section('title', $contrat->libelleComplet())
@section('page_title', $contrat->libelleComplet())
@section('page_icon', 'fa-file-contract')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('contrats.contrats.index') }}">Contrats</a></li>
    <li>{{ $contrat->reference }}</li>
@endsection

@section('page_actions')
    @if($contrat->estModifiable())
        @can('permission', 'contrats.manage')
            <a href="{{ route('contrats.contrats.edit', $contrat) }}" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Modifier
            </a>
        @endcan
    @endif

    @if($contrat->statut === \App\Domain\Contrats\Enums\StatutContrat::BROUILLON)
        @can('permission', 'contrats.manage')
            <button type="button" class="btn btn-primary js-soumettre">
                <i class="fas fa-paper-plane"></i> Soumettre
            </button>
        @endcan
    @endif

    @if($contrat->statut === \App\Domain\Contrats\Enums\StatutContrat::SOUMIS)
        @can('permission', 'contrats.validate')
            <button type="button" class="btn btn-success js-valider">
                <i class="fas fa-check"></i> Valider
            </button>
            <button type="button" class="btn btn-warning js-retourner">
                <i class="fas fa-undo"></i> Retourner
            </button>
        @endcan
    @endif

    @if($contrat->statut === \App\Domain\Contrats\Enums\StatutContrat::VALIDE)
        @can('permission', 'contrats.sign')
            <button type="button" class="btn btn-success js-signer">
                <i class="fas fa-signature"></i> Référencer la signature
            </button>
        @endcan
    @endif

    @if(! in_array($contrat->statut, [\App\Domain\Contrats\Enums\StatutContrat::SIGNE, \App\Domain\Contrats\Enums\StatutContrat::ANNULE]))
        @can('permission', 'contrats.validate')
            <button type="button" class="btn btn-outline-danger js-annuler">
                <i class="fas fa-times"></i> Annuler
            </button>
        @endcan
    @endif

    @if($contrat->estSigne())
        @can('permission', 'contrats.manage')
            <a href="{{ route('contrats.contrats.create-avenant', $contrat) }}" class="btn btn-primary">
                <i class="fas fa-plus-square"></i> Créer un avenant
            </a>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $contrat->statut->couleur() }} fs-6">
                    {{ $contrat->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Salarié</div>
                <a href="{{ route('personnel.salaries.show', $contrat->salarie) }}" class="fw-bold">
                    {{ $contrat->salarie?->nom_complet }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Type</div>
                <div class="fw-bold">{{ $contrat->type_contrat }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Révision</div>
                <div class="fw-bold">{{ $contrat->revision }}</div>
            </div>
        </div>
    </div>
</div>

@if($contrat->estAvenant() && $contrat->parent)
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Cet avenant modifie le contrat
        <a href="{{ route('contrats.contrats.show', $contrat->parent) }}"><strong>{{ $contrat->parent->reference }}</strong></a>.
    </div>
@endif

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-identite">
        <i class="fas fa-id-card"></i> Identité</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-conditions">
        <i class="fas fa-list"></i> Conditions</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-essai">
        <i class="fas fa-hourglass-half"></i> Période d'essai</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-pieces">
        <i class="fas fa-paperclip"></i> Pièces ({{ $contrat->pieces->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-alertes">
        <i class="fas fa-bell"></i> Alertes ({{ $contrat->alertes->where('en_cours', true)->count() }})</a></li>
    @if($contrat->avenants->count() > 0)
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-avenants">
            <i class="fas fa-plus-square"></i> Avenants ({{ $contrat->avenants->count() }})</a></li>
    @endif
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-historique">
        <i class="fas fa-history"></i> Historique</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-identite">
        @include('contrats.contrats.partials._identite')
    </div>
    <div class="tab-pane fade" id="tab-conditions">
        @include('contrats.contrats.partials._conditions')
    </div>
    <div class="tab-pane fade" id="tab-essai">
        @include('contrats.contrats.partials._essai')
    </div>
    <div class="tab-pane fade" id="tab-pieces">
        @include('contrats.contrats.partials._pieces')
    </div>
    <div class="tab-pane fade" id="tab-alertes">
        @include('contrats.contrats.partials._alertes')
    </div>
    @if($contrat->avenants->count() > 0)
        <div class="tab-pane fade" id="tab-avenants">
            @include('contrats.contrats.partials._avenants')
        </div>
    @endif
    <div class="tab-pane fade" id="tab-historique">
        @include('contrats.contrats.partials._historique')
    </div>
</div>

{{-- Modals --}}
@include('contrats.contrats.partials._modal-piece')
@include('contrats.contrats.partials._modal-valider')
@include('contrats.contrats.partials._modal-retourner')
@include('contrats.contrats.partials._modal-annuler')
@include('contrats.contrats.partials._modal-signer')
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    // Soumettre
    $(document).on('click', '.js-soumettre', function () {
        Swal.fire({
            title: 'Soumettre ce contrat ?',
            text: 'Le contrat passera au statut « Soumis » pour validation.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, soumettre',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('contrats.contrats.soumettre', $contrat) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function () { window.showToastThenReload('Contrat soumis.'); })
                .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });

    // Valider → ouvrir modal
    $(document).on('click', '.js-valider', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-valider')).show();
    });

    // Retourner → ouvrir modal
    $(document).on('click', '.js-retourner', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-retourner')).show();
    });

    // Annuler → ouvrir modal
    $(document).on('click', '.js-annuler', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-annuler')).show();
    });

    // Signer → ouvrir modal
    $(document).on('click', '.js-signer', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-signer')).show();
    });

    // Soumission des modals par AJAX
    function soumettreModal(formSelector, modalId, url) {
        $(formSelector).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url: url,
                method: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) {
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                window.showToastThenReload(r.message || 'Enregistré.');
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
    }

    soumettreModal('#form-valider', 'modal-valider', "{{ route('contrats.contrats.valider', $contrat) }}");
    soumettreModal('#form-retourner', 'modal-retourner', "{{ route('contrats.contrats.retourner-brouillon', $contrat) }}");
    soumettreModal('#form-annuler', 'modal-annuler', "{{ route('contrats.contrats.annuler', $contrat) }}");
    soumettreModal('#form-signer', 'modal-signer', "{{ route('contrats.contrats.signer', $contrat) }}");
});
</script>
@endpush