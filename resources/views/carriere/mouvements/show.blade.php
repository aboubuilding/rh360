@extends('layouts.app')

@section('title', 'Mouvement ' . $mouvement->numero_mouvement)
@section('page_title', $mouvement->numero_mouvement)
@section('page_icon', 'fa-file-signature')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.mouvements.index') }}">Actes de carrière</a></li>
    <li>{{ $mouvement->numero_mouvement }}</li>
@endsection

@section('page_actions')
    @if($mouvement->statut->estModifiable())
        @can('permission', 'carriere.manage')
            <a href="{{ route('carriere.mouvements.edit', $mouvement) }}" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Modifier
            </a>
        @endcan
    @endif

    @if($mouvement->statut->value === 'draft')
        @can('permission', 'carriere.manage')
            <button type="button" class="btn btn-primary js-soumettre">
                <i class="fas fa-paper-plane"></i> Soumettre
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'proposed')
        @can('permission', 'carriere.manage')
            <button type="button" class="btn btn-info js-controler">
                <i class="fas fa-check"></i> Contrôler
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'to_check')
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-primary js-verifier">
                <i class="fas fa-search"></i> Vérifier
            </button>
            <button type="button" class="btn btn-outline-danger js-rejeter">
                <i class="fas fa-times"></i> Rejeter
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'checked')
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-success js-valider">
                <i class="fas fa-check-double"></i> Valider
            </button>
            <button type="button" class="btn btn-outline-danger js-rejeter">
                <i class="fas fa-times"></i> Rejeter
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'validated')
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-primary js-programmer">
                <i class="fas fa-calendar-alt"></i> Programmer
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'scheduled' && $mouvement->date_effet && $mouvement->date_effet->isPast())
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-success js-appliquer">
                <i class="fas fa-play"></i> Appliquer maintenant
            </button>
        @endcan
    @endif

    @if($mouvement->statut->value === 'effective')
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-dark js-cloturer">
                <i class="fas fa-flag-checkered"></i> Clôturer
            </button>
        @endcan
    @endif

    @if(! $mouvement->statut->estFinal())
        @can('permission', 'carriere.validate')
            <button type="button" class="btn btn-outline-danger js-annuler">
                <i class="fas fa-ban"></i> Annuler
            </button>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $mouvement->statut->couleur() }} fs-6">
                    {{ $mouvement->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Type</div>
                <span class="badge bg-{{ $mouvement->type_mouvement->couleur() }} fs-6">
                    {{ $mouvement->type_mouvement->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Salarié</div>
                <a href="{{ route('personnel.salaries.show', $mouvement->salarie) }}" class="fw-bold">
                    {{ $mouvement->salarie?->nom_complet }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Prochaine étape</div>
                <div class="fw-bold text-primary">
                    {{ $mouvement->prochaineEtape() ?? '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-identite">
        <i class="fas fa-id-card"></i> Identité</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-situation">
        <i class="fas fa-exchange-alt"></i> Situation</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-circuit">
        <i class="fas fa-route"></i> Circuit</a></li>
    @if($mouvement->instantane)
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-instantane">
            <i class="fas fa-camera"></i> Instantané</a></li>
    @endif
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-identite">
        @include('carriere.mouvements.partials._identite')
    </div>
    <div class="tab-pane fade" id="tab-situation">
        @include('carriere.mouvements.partials._situation')
    </div>
    <div class="tab-pane fade" id="tab-circuit">
        @include('carriere.mouvements.partials._circuit')
    </div>
    @if($mouvement->instantane)
        <div class="tab-pane fade" id="tab-instantane">
            @include('carriere.mouvements.partials._instantane')
        </div>
    @endif
</div>

{{-- Modals --}}
@include('carriere.mouvements.partials._modal-soumettre')
@include('carriere.mouvements.partials._modal-controler')
@include('carriere.mouvements.partials._modal-valider')
@include('carriere.mouvements.partials._modal-rejeter')
@include('carriere.mouvements.partials._modal-programmer')
@include('carriere.mouvements.partials._modal-annuler')
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    // Actions directes sans modal
    $(document).on('click', '.js-soumettre', function () {
        Swal.fire({
            title: 'Soumettre ce mouvement ?',
            text: 'Le mouvement sera transmis pour contrôle.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, soumettre',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('carriere.mouvements.soumettre', $mouvement) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function (resp) { window.showToastThenReload(resp.message || 'Soumis.'); })
                .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });

    $(document).on('click', '.js-appliquer', function () {
        Swal.fire({
            title: 'Appliquer ce mouvement maintenant ?',
            text: 'L\'affectation et la situation de carrière seront mises à jour.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Oui, appliquer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('carriere.mouvements.appliquer', $mouvement) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function (resp) { window.showToastThenReload(resp.message || 'Appliqué.'); })
                .fail(function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Erreur.';
                    window.showToast(msg, 'error');
                });
        });
    });

    // Modals
    function ouvrir(modalId) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
    }

    $(document).on('click', '.js-controler', () => ouvrir('modal-controler'));
    $(document).on('click', '.js-verifier',  () => ouvrir('modal-valider'));
    $(document).on('click', '.js-valider',   () => ouvrir('modal-valider'));
    $(document).on('click', '.js-rejeter',   () => ouvrir('modal-rejeter'));
    $(document).on('click', '.js-programmer',() => ouvrir('modal-programmer'));
    $(document).on('click', '.js-annuler',   () => ouvrir('modal-annuler'));
    $(document).on('click', '.js-cloturer',  function () {
        Swal.fire({
            title: 'Clôturer ce mouvement ?',
            text: 'Le mouvement sera marqué comme terminé.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, clôturer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('carriere.mouvements.cloturer', $mouvement) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function (resp) { window.showToastThenReload(resp.message || 'Clôturé.'); })
                .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });

    // Soumission AJAX des modals
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
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) {
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                window.showToastThenReload(r.message || 'Enregistré.');
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
    }

    soumettreModal('#form-controler', 'modal-controler', "{{ route('carriere.mouvements.controler', $mouvement) }}");
    soumettreModal('#form-valider',    'modal-valider',    "{{ route('carriere.mouvements.valider', $mouvement) }}");
    soumettreModal('#form-rejeter',    'modal-rejeter',    "{{ route('carriere.mouvements.rejeter', $mouvement) }}");
    soumettreModal('#form-programmer', 'modal-programmer', "{{ route('carriere.mouvements.programmer', $mouvement) }}");
    soumettreModal('#form-annuler',    'modal-annuler',    "{{ route('carriere.mouvements.annuler', $mouvement) }}");
});
</script>
@endpush