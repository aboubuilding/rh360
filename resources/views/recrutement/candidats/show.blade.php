@extends('layouts.app')

@section('title', 'Candidat ' . $candidat->nom_complet)
@section('page_title', $candidat->nom_complet)
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('recrutement.candidats.index') }}">Candidats</a></li>
    <li>{{ $candidat->nom_complet }}</li>
@endsection

@section('page_actions')
    @if($candidat->estEnCours())
        @can('permission', 'recrutement.manage')
            <button type="button" class="btn btn-secondary js-changer-etape">
                <i class="fas fa-arrow-right"></i> Changer d'étape
            </button>
            @if($candidat->decision?->value === 'en_attente' || $candidat->decision?->value === 'liste_attente')
                <button type="button" class="btn btn-success js-retenir">
                    <i class="fas fa-check"></i> Retenir
                </button>
                <button type="button" class="btn btn-outline-danger js-refuser">
                    <i class="fas fa-times"></i> Refuser
                </button>
            @endif
            @if($candidat->decision?->value === 'retenu' && ! $candidat->date_integration)
                <button type="button" class="btn btn-primary js-integrer">
                    <i class="fas fa-sign-in-alt"></i> Intégrer
                </button>
            @endif
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Étape</div>
                <span class="badge bg-{{ $candidat->etape?->couleur() }} fs-6">
                    {{ $candidat->etape?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Décision</div>
                <span class="badge bg-{{ $candidat->decision?->couleur() }} fs-6">
                    {{ $candidat->decision?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Score</div>
                <div class="fw-bold fs-4">{{ $candidat->score ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Date d'intégration</div>
                <div class="fw-bold">{{ $candidat->date_integration?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Besoin associé</dt>
            <dd class="col-sm-9">
                @if($candidat->besoin)
                    <a href="{{ route('recrutement.besoins.show', $candidat->besoin) }}">
                        {{ $candidat->besoin->reference }} — {{ $candidat->besoin->intitule_poste }}
                    </a>
                @else — @endif
            </dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9">{{ $candidat->email ?? '—' }}</dd>

            <dt class="col-sm-3">Téléphone</dt>
            <dd class="col-sm-9">{{ $candidat->telephone ?? '—' }}</dd>

            <dt class="col-sm-3">Source</dt>
            <dd class="col-sm-9">{{ $candidat->source?->libelle() }}</dd>

            <dt class="col-sm-3">Date d'entretien</dt>
            <dd class="col-sm-9">{{ $candidat->date_entretien?->format('d/m/Y H:i') ?? '—' }}</dd>

            @if($candidat->observations)
                <dt class="col-sm-3">Observations</dt>
                <dd class="col-sm-9">{{ $candidat->observations }}</dd>
            @endif
        </dl>
    </div>
</div>

@if($etapesPossibles->count() > 0)
    <div class="card mt-3">
        <div class="card-header"><strong>Étapes possibles</strong></div>
        <div class="card-body">
            @foreach($etapesPossibles as $e)
                <span class="badge bg-{{ $e->couleur() }} me-2">{{ $e->libelle() }}</span>
            @endforeach
        </div>
    </div>
@endif

@include('recrutement.candidats.partials._modal-changer-etape')
@include('recrutement.candidats.partials._modal-retenir')
@include('recrutement.candidats.partials._modal-refuser')
@include('recrutement.candidats.partials._modal-integrer')
@endsection

@push('js')
<script>
$(function () {
    const ACTIONS = {
        'changer-etape': { modal: 'modal-changer-etape', url: "{{ route('recrutement.candidats.changer-etape', $candidat) }}" },
        'retenir':       { modal: 'modal-retenir',       url: "{{ route('recrutement.candidats.retenir', $candidat) }}" },
        'refuser':       { modal: 'modal-refuser',       url: "{{ route('recrutement.candidats.refuser', $candidat) }}" },
        'integrer':      { modal: 'modal-integrer',      url: "{{ route('recrutement.candidats.integrer', $candidat) }}" },
    };

    Object.keys(ACTIONS).forEach(function (key) {
        $(document).on('click', '.js-' + key, function () {
            const $form = $('#form-' + key);
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            bootstrap.Modal.getOrCreateInstance(document.getElementById(ACTIONS[key].modal)).show();
        });

        $('#form-' + key).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();

            $form.find('.is-invalid').removeClass('is-invalid');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

            $.ajax({
                url: ACTIONS[key].url,
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) {
                bootstrap.Modal.getInstance(document.getElementById(ACTIONS[key].modal)).hide();
                window.showToastThenReload(r.message || 'Enregistré.');
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
    });
});
</script>
@endpush