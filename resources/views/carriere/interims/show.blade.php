@extends('layouts.app')

@section('title', 'Intérim ' . $interim->numero_mouvement)
@section('page_title', $interim->numero_mouvement)
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>{{ $interim->numero_mouvement }}</li>
@endsection

@section('page_actions')
    @if(! $interim->statut->estFinal())
        @can('permission', 'carriere.manage')
            <button type="button" class="btn btn-warning js-prolonger">
                <i class="fas fa-clock"></i> Prolonger
            </button>
            <button type="button" class="btn btn-dark js-cloturer">
                <i class="fas fa-flag-checkered"></i> Clôturer
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
                <span class="badge bg-{{ $interim->statut->couleur() }} fs-6">
                    {{ $interim->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Salarié</div>
                <a href="{{ route('personnel.salaries.show', $interim->salarie) }}" class="fw-bold">
                    {{ $interim->salarie?->nom_complet }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Début</div>
                <div class="fw-bold">{{ $interim->date_effet?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Fin prévue</div>
                <div class="fw-bold">{{ $interim->date_fin_prevue?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Poste permanent d'origine</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Structure</dt>
                    <dd class="col-sm-7">{{ $interim->structureDepart?->nom ?? '—' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $interim->posteDepart?->intitule ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Poste temporaire assuré</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Structure</dt>
                    <dd class="col-sm-7">{{ $interim->structureCible?->nom ?? '—' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $interim->posteCible?->intitule ?? '—' }}</dd>

                    <dt class="col-sm-5">Lieu</dt>
                    <dd class="col-sm-7">{{ $interim->lieu_affectation_cible ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($interim->motif)
            <hr>
            <h6 class="text-muted">Motif</h6>
            <p>{{ $interim->motif }}</p>
        @endif

        @if($interim->date_fin_reelle)
            <hr>
            <h6 class="text-muted">Clôture</h6>
            <dl class="row mb-0">
                <dt class="col-sm-3">Date de fin réelle</dt>
                <dd class="col-sm-3">{{ $interim->date_fin_reelle->format('d/m/Y') }}</dd>

                <dt class="col-sm-3">Motif de clôture</dt>
                <dd class="col-sm-3">{{ $interim->motif_cloture ?? '—' }}</dd>
            </dl>
        @endif
    </div>
</div>

@include('carriere.interims.partials._modal-prolonger')
@include('carriere.interims.partials._modal-cloturer')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-prolonger', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-prolonger')).show();
    });
    $(document).on('click', '.js-cloturer', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cloturer')).show();
    });

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
                } else {
                    window.showToast('Erreur.', 'error');
                }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-prolonger', 'modal-prolonger', "{{ route('carriere.interims.prolonger', $interim) }}");
    soumettreModal('#form-cloturer',  'modal-cloturer',  "{{ route('carriere.interims.cloturer', $interim) }}");
});
</script>
@endpush