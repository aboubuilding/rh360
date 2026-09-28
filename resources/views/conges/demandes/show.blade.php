@extends('layouts.app')

@section('title', 'Demande ' . $demande->numero_demande)
@section('page_title', $demande->numero_demande)
@section('page_icon', 'fa-plane-departure')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.demandes.index') }}">Demandes</a></li>
    <li>{{ $demande->numero_demande }}</li>
@endsection

@section('page_actions')
    @if($demande->statut->value === 'draft')
        @can('permission', 'conges.manage')
            <a href="{{ route('conges.demandes.edit', $demande) }}" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <button type="button" class="btn btn-primary js-soumettre">
                <i class="fas fa-paper-plane"></i> Soumettre
            </button>
        @endcan
    @endif

    @if($demande->statut->value === 'submitted')
        @can('permission', 'conges.validate')
            <button type="button" class="btn btn-success js-autoriser">
                <i class="fas fa-check"></i> Autoriser
            </button>
            <button type="button" class="btn btn-outline-danger js-refuser">
                <i class="fas fa-times"></i> Refuser
            </button>
        @endcan
    @endif

    @if(in_array($demande->statut->value, ['authorized', 'scheduled']))
        @can('permission', 'conges.validate')
            @if($demande->statut->value === 'authorized')
                <button type="button" class="btn btn-primary js-programmer">
                    <i class="fas fa-calendar-check"></i> Programmer
                </button>
            @endif
            <button type="button" class="btn btn-warning js-demarrer">
                <i class="fas fa-play"></i> Démarrer
            </button>
        @endcan
    @endif

    @if($demande->statut->value === 'in_progress')
        @can('permission', 'conges.validate')
            <button type="button" class="btn btn-success js-reprise">
                <i class="fas fa-flag-checkered"></i> Confirmer la reprise
            </button>
        @endcan
    @endif

    @if(in_array($demande->statut->value, ['authorized', 'scheduled', 'in_progress']))
        <a href="{{ route('conges.demandes.acte', $demande) }}" target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Acte PDF
        </a>
    @endif

    @if(! $demande->statut->estFinal())
        @can('permission', 'conges.validate')
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
                <span class="badge bg-{{ $demande->statut->couleur() }} fs-6">
                    {{ $demande->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Salarié</div>
                <a href="{{ route('personnel.salaries.show', $demande->salarie) }}" class="fw-bold">
                    {{ $demande->salarie?->nom_complet }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Type</div>
                <div class="fw-bold">{{ $demande->typeConge?->nom }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Durée</div>
                <div class="fw-bold">{{ number_format($demande->duree_jours, 2) }} j</div>
            </div>
        </div>
    </div>
</div>

@if($demande->estEnRetard())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Reprise en retard.</strong> La date de reprise prévue ({{ $demande->date_reprise->format('d/m/Y') }}) est dépassée.
    </div>
@endif

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-identite">
        <i class="fas fa-id-card"></i> Identité</a></li>
    @if($solde)
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-soldes">
            <i class="fas fa-balance-scale"></i> Solde</a></li>
    @endif
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-circuit">
        <i class="fas fa-route"></i> Circuit</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-identite">
        @include('conges.demandes.partials._identite')
    </div>
    @if($solde)
        <div class="tab-pane fade" id="tab-soldes">
            @include('conges.demandes.partials._soldes')
        </div>
    @endif
    <div class="tab-pane fade" id="tab-circuit">
        @include('conges.demandes.partials._circuit')
    </div>
</div>

{{-- Modals --}}
@include('conges.demandes.partials._modal-soumettre')
@include('conges.demandes.partials._modal-autoriser')
@include('conges.demandes.partials._modal-refuser')
@include('conges.demandes.partials._modal-programmer')
@include('conges.demandes.partials._modal-demarrer')
@include('conges.demandes.partials._modal-reprise')
@include('conges.demandes.partials._modal-annuler')
@endsection

@push('js')
<script>
$(function () {
    function ouvrir(id) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
    }

    $(document).on('click', '.js-soumettre', function () {
        Swal.fire({
            title: 'Soumettre cette demande ?',
            text: 'Elle sera transmise pour validation.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, soumettre',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('conges.demandes.soumettre', $demande) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function (resp) { window.showToastThenReload(resp.message || 'Soumise.'); })
                .fail(function (xhr) {
                    window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                });
        });
    });

    $(document).on('click', '.js-demarrer', function () {
        Swal.fire({
            title: 'Démarrer ce congé ?',
            text: 'Le congé passera au statut « En cours ».',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, démarrer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post("{{ route('conges.demandes.demarrer', $demande) }}",
                { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function (resp) { window.showToastThenReload(resp.message || 'Démarré.'); })
                .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });

    $(document).on('click', '.js-autoriser',    () => ouvrir('modal-autoriser'));
    $(document).on('click', '.js-refuser',      () => ouvrir('modal-refuser'));
    $(document).on('click', '.js-programmer',   () => ouvrir('modal-programmer'));
    $(document).on('click', '.js-reprise',      () => ouvrir('modal-reprise'));
    $(document).on('click', '.js-annuler',      () => ouvrir('modal-annuler'));

    function soumettreModal(formSelector, modalId, url) {
        $(formSelector).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

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
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast('Erreur.', 'error');
                }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-autoriser', 'modal-autoriser', "{{ route('conges.demandes.autoriser', $demande) }}");
    soumettreModal('#form-refuser',   'modal-refuser',   "{{ route('conges.demandes.refuser', $demande) }}");
    soumettreModal('#form-programmer','modal-programmer',"{{ route('conges.demandes.programmer', $demande) }}");
    soumettreModal('#form-reprise',   'modal-reprise',   "{{ route('conges.demandes.confirmer-reprise', $demande) }}");
    soumettreModal('#form-annuler',   'modal-annuler',   "{{ route('conges.demandes.annuler', $demande) }}");
});
</script>
@endpush