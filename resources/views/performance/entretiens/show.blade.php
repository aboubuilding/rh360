@extends('layouts.app')

@section('title', 'Entretien ' . $entretien->salarie?->nom_complet)
@section('page_title', 'Entretien — ' . $entretien->salarie?->nom_complet)
@section('page_icon', 'fa-comments')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('performance.entretiens.index') }}">Entretiens</a></li>
    <li>{{ $entretien->salarie?->nom_complet }}</li>
@endsection

@section('page_actions')
    @if(in_array($entretien->statut?->value, ['a_preparer', 'auto_evalue']))
        <button type="button" class="btn btn-secondary js-auto-evaluer">
            <i class="fas fa-user-edit"></i> Saisir l'auto-évaluation
        </button>
    @endif
    @if(in_array($entretien->statut?->value, ['a_preparer', 'auto_evalue', 'realise']))
        <button type="button" class="btn btn-primary js-realiser">
            <i class="fas fa-edit"></i> Réaliser l'entretien
        </button>
    @endif
    @if($entretien->statut?->value === 'realise')
        @can('permission', 'performance.manage')
            <form method="POST" action="{{ route('performance.entretiens.valider', $entretien) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Valider
                </button>
            </form>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Statut</div>
                <span class="badge bg-{{ $entretien->statut?->couleur() }} fs-6">
                    {{ $entretien->statut?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Note auto-évaluation</div>
                <div class="fw-bold fs-5">{{ $entretien->note_auto_evaluation ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Note manager</div>
                <div class="fw-bold fs-5">{{ $entretien->note_manager ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-light">
            <div class="card-body">
                <div class="text-muted small">Note finale</div>
                <div class="fw-bold fs-4 text-primary">{{ $entretien->note_finale ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Campagne</dt>
            <dd class="col-sm-9">{{ $entretien->campagne?->intitule }}</dd>

            <dt class="col-sm-3">Date de l'entretien</dt>
            <dd class="col-sm-9">{{ $entretien->date_entretien?->format('d/m/Y H:i') ?? '—' }}</dd>

            @if($entretien->points_forts)
                <dt class="col-sm-3">Points forts</dt>
                <dd class="col-sm-9">{{ $entretien->points_forts }}</dd>
            @endif

            @if($entretien->besoins_developpement)
                <dt class="col-sm-3">Besoins de développement</dt>
                <dd class="col-sm-9">{{ $entretien->besoins_developpement }}</dd>
            @endif

            @if($entretien->commentaire_manager)
                <dt class="col-sm-3">Commentaire manager</dt>
                <dd class="col-sm-9">{{ $entretien->commentaire_manager }}</dd>
            @endif

            @if($entretien->action_amelioration)
                <dt class="col-sm-3">Plan d'amélioration</dt>
                <dd class="col-sm-9">
                    {{ $entretien->action_amelioration }}
                    @if($entretien->date_echeance_amelioration)
                        <br><small class="text-muted">Échéance : {{ $entretien->date_echeance_amelioration->format('d/m/Y') }}</small>
                    @endif
                </dd>
            @endif
        </dl>
    </div>
</div>

@include('performance.entretiens.partials._modal-auto-evaluation')
@include('performance.entretiens.partials._modal-realiser')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-auto-evaluer', () => {
        $('#note_auto_evaluation').val("{{ $entretien->note_auto_evaluation }}");
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-auto-evaluation')).show();
    });
    $(document).on('click', '.js-realiser', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-realiser')).show();
    });

    function soumettreModal(formSelector, modalId, url) {
        $(formSelector).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();

            $form.find('.is-invalid').removeClass('is-invalid');
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
                } else { window.showToast('Erreur.', 'error'); }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-auto-evaluation', 'modal-auto-evaluation', "{{ route('performance.entretiens.auto-evaluer', $entretien) }}");
    soumettreModal('#form-realiser',        'modal-realiser',        "{{ route('performance.entretiens.realiser', $entretien) }}");
});
</script>
@endpush