@extends('layouts.app')

@section('title', 'Paie ' . $periode->libelle)
@section('page_title', 'Période de paie — ' . $periode->libelle)
@section('page_icon', 'fa-money-check-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.periodes.index') }}">Périodes</a></li>
    <li>{{ $periode->libelle }}</li>
@endsection

@section('page_actions')
    @if(! $periode->estFigee())
        @can('permission', 'paie.manage')
            <button type="button" class="btn btn-secondary js-saisir-element">
                <i class="fas fa-edit"></i> Saisir un élément
            </button>
        @endcan
        @can('permission', 'paie.calculer')
            <form method="POST" action="{{ route('paie.periodes.calculer', $periode) }}"
                  class="d-inline js-form-calculer">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-calculator"></i> Calculer la paie
                </button>
            </form>
        @endcan
        @can('permission', 'paie.valider')
            <button type="button" class="btn btn-primary js-valider-periode">
                <i class="fas fa-lock"></i> Valider définitivement
            </button>
        @endcan
    @else
        <div class="alert alert-success mb-0 d-inline-flex align-items-center">
            <i class="fas fa-lock"></i>
            <span class="ms-2">Période validée le {{ $periode->valide_le?->format('d/m/Y H:i') }}</span>
        </div>
        @if(auth()->user()->estSuperAdmin())
            <button type="button" class="btn btn-outline-warning js-reouvrir-periode">
                <i class="fas fa-unlock"></i> Rouvrir
            </button>
        @endif
    @endif
    <a href="{{ route('paie.periodes.journal', $periode) }}" class="btn btn-secondary">
        <i class="fas fa-file-excel"></i> Journal Excel
    </a>
@endsection

@section('contenu')
@include('paie.periodes.partials._entete')

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-bulletins">
        <i class="fas fa-file-invoice-dollar"></i> Bulletins ({{ $periode->bulletins->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-saisies">
        <i class="fas fa-list-alt"></i> Saisies variables</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-bulletins">
        @include('paie.periodes.partials._bulletins')
    </div>
    <div class="tab-pane fade" id="tab-saisies">
        @include('paie.periodes.partials._saisies')
    </div>
</div>

@include('paie.periodes._modal-saisir')
@include('paie.periodes.partials._modal-valider')
@endsection

@push('js')
<script>
$(function () {
    // Calcul
    $(document).on('submit', '.js-form-calculer', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        Swal.fire({
            title: 'Calculer la paie ?',
            text: 'Les bulletins de cette période seront générés ou recalculés.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Oui, calculer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calcul...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (resp) { window.showToastThenReload(resp.message || 'Calcul effectué.'); })
            .fail(function (xhr) { window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error'); })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    });

    // Validation
    $(document).on('click', '.js-valider-periode', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-valider')).show();
    });

    $('#form-valider').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-valider')).hide();
            window.showToastThenReload(r.message || 'Période validée.');
        })
        .fail(function (xhr) {
            window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });

    // Réouverture
    $(document).on('click', '.js-reouvrir-periode', function () {
        Swal.fire({
            title: 'Rouvrir cette période ?',
            text: 'Cette action exceptionnelle sera tracée dans le journal d\'audit.',
            input: 'textarea',
            inputLabel: 'Motif de réouverture (obligatoire)',
            inputPlaceholder: 'Expliquez pourquoi cette période doit être rouverte...',
            inputValidator: (value) => {
                if (!value || value.trim().length < 10) {
                    return 'Le motif doit contenir au moins 10 caractères.';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, rouvrir',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $.ajax({
                url: "{{ route('paie.periodes.reouvrir', $periode) }}",
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    motif: r.value,
                },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (resp) { window.showToastThenReload(resp.message || 'Période rouverte.'); })
            .fail(function (xhr) { window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error'); });
        });
    });

    // Saisie élément variable
    $(document).on('click', '.js-saisir-element', function () {
        $('#form-saisir')[0].reset();
        $('#form-saisir').find('.is-invalid').removeClass('is-invalid');
        $('#form-saisir').find('.invalid-feedback').remove();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-saisir')).show();
    });

    $('#form-saisir').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: "{{ route('paie.periodes.saisir', $periode) }}",
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-saisir')).hide();
            window.showToastThenReload(r.message || 'Saisie enregistrée.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });

    // Suppression saisie
    $(document).on('click', '.js-supprimer-saisie', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Supprimer cette saisie ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $.ajax({
                url: "{{ url('/paie/periodes/' . $periode->id . '/saisies/__ID__') }}".replace('__ID__', id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (resp) { window.showToastThenReload(resp.message || 'Saisie supprimée.'); })
            .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });
});
</script>
@endpush