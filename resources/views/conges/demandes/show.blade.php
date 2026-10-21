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
            @can('refuser', $demande)
                <button type="button" class="btn btn-outline-danger js-refuser">
                    <i class="fas fa-times"></i> Refuser
                </button>
            @endcan
        @endcan
    @endif

    @can('reporter', $demande)
        <button type="button" class="btn btn-outline-secondary js-reporter">
            <i class="fas fa-calendar-alt"></i> Décaler le départ
        </button>
    @endcan

    @can('rappeler', $demande)
        <button type="button" class="btn btn-outline-warning js-rappel">
            <i class="fas fa-phone"></i> Rappeler
        </button>
    @endcan

    @can('requalifier', $demande)
        <button type="button" class="btn btn-outline-primary js-prolonger">
            <i class="fas fa-calendar-plus"></i> Prolonger le délai
        </button>
        <button type="button" class="btn btn-outline-dark js-requalifier">
            <i class="fas fa-exchange-alt"></i> Requalifier
        </button>
    @endcan

    @if($demande->statut->value === 'authorized')
        @can('permission', 'conges.validate')
            <button type="button" class="btn btn-primary js-programmer">
                <i class="fas fa-calendar-check"></i> Programmer
            </button>
        @endcan
    @endif

    {{-- Départ constaté à partir de la date de départ uniquement --}}
    @can('demarrer', $demande)
        <button type="button" class="btn btn-warning js-demarrer">
            <i class="fas fa-play"></i> Démarrer
        </button>
    @endcan

    {{-- À partir de la date de reprise prévue uniquement (CDC §4 4.1) --}}
    @can('confirmerReprise', $demande)
        <button type="button" class="btn btn-success js-reprise">
            <i class="fas fa-flag-checkered"></i> Confirmer la reprise
        </button>
    @endcan

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

@if($demande->statut->value === 'awaiting_document')
    <div class="alert alert-warning">
        <i class="fas fa-hourglass-half"></i>
        <strong>En attente de justificatif.</strong>
        @if($demande->echeance_justificatif)
            À remettre au plus tard le {{ $demande->echeance_justificatif->format('d/m/Y') }} ; passé ce délai, la demande sera requalifiée.
        @endif
        @if($demande->typeConge?->estDeDroit())
            Absence de droit : elle sera validée dès que le justificatif sera reconnu conforme.
        @endif
    </div>
@endif

@if($demande->statut->value === 'requalified')
    <div class="alert alert-dark">
        <i class="fas fa-exchange-alt"></i>
        <strong>Demande requalifiée</strong> le {{ $demande->requalifiee_le?->format('d/m/Y') }} :
        {{ $demande->requalification === 'deduction_conge' ? 'jours déduits du congé payé.' : 'absence non justifiée.' }}
        @if($demande->absence)
            <a href="{{ route('conges.absences.show', $demande->absence) }}">Voir l'absence</a>
        @endif
    </div>
@endif

@if($demande->validation_automatique && in_array($demande->statut->value, ['authorized', 'scheduled', 'in_progress', 'resumed']))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> Absence de droit : validée automatiquement.
    </div>
@endif

@if($compteur)
    <div class="alert alert-{{ $compteur['reste'] < 0 ? 'danger' : 'light' }} border">
        <i class="fas fa-gauge"></i>
        <strong>Plafond :</strong> {{ (float) $compteur['plafond'] }} {{ $compteur['unite'] }} {{ $compteur['periode'] }}{{ $demande->membreFoyer ? ' pour '.$demande->membreFoyer->prenoms : '' }} — déjà décompté : {{ (float) $compteur['consomme'] }} ; cette demande : {{ (float) $compteur['demande'] }} ; reste après : {{ (float) $compteur['reste'] }}.
    </div>
@endif

@if($demande->force_majeure)
    <div class="alert alert-info">
        <i class="fas fa-bolt"></i> <strong>Force majeure :</strong> {{ $demande->motif_force_majeure }}
    </div>
@endif

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
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-justificatifs">
        <i class="fas fa-paperclip"></i> Justificatifs</a></li>
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
            @if($lots !== null)
                @include('conges.demandes.partials._conge-paye')
            @endif
        </div>
    @endif
    <div class="tab-pane fade" id="tab-justificatifs">
        @include('conges.demandes.partials._justificatifs')
    </div>
    <div class="tab-pane fade" id="tab-circuit">
        @include('conges.demandes.partials._circuit')
        @include('conges.demandes.partials._historique')
    </div>
</div>

{{-- Modals (soumettre et démarrer passent par une confirmation SweetAlert) --}}
@include('conges.demandes.partials._modal-autoriser')
@include('conges.demandes.partials._modal-refuser')
@include('conges.demandes.partials._modal-programmer')
@include('conges.demandes.partials._modal-reprise')
@include('conges.demandes.partials._modal-annuler')
@include('conges.demandes.partials._modal-requalifier')
@include('conges.demandes.partials._modal-prolonger')
@include('conges.demandes.partials._modal-reporter')
@include('conges.demandes.partials._modal-rappel')
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
                .fail(function (xhr) { window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error'); });
        });
    });

    $(document).on('click', '.js-autoriser',    () => ouvrir('modal-autoriser'));
    $(document).on('click', '.js-refuser',      () => ouvrir('modal-refuser'));
    $(document).on('click', '.js-programmer',   () => ouvrir('modal-programmer'));
    $(document).on('click', '.js-reprise',      () => ouvrir('modal-reprise'));
    $(document).on('click', '.js-annuler',      () => ouvrir('modal-annuler'));
    $(document).on('click', '.js-requalifier',  () => ouvrir('modal-requalifier'));
    $(document).on('click', '.js-prolonger',    () => ouvrir('modal-prolonger'));
    $(document).on('click', '.js-reporter',     () => ouvrir('modal-reporter'));
    $(document).on('click', '.js-rappel',       () => ouvrir('modal-rappel'));

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
    soumettreModal('#form-requalifier', 'modal-requalifier', "{{ route('conges.demandes.requalifier', $demande) }}");
    soumettreModal('#form-prolonger', 'modal-prolonger', "{{ route('conges.demandes.prolonger-echeance', $demande) }}");
    soumettreModal('#form-reporter', 'modal-reporter', "{{ route('conges.demandes.reporter', $demande) }}");
    soumettreModal('#form-rappel', 'modal-rappel', "{{ route('conges.demandes.rappeler', $demande) }}");
});
</script>
@endpush