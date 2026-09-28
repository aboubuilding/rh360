@extends('layouts.app')

@section('title', 'Session ' . $session->intitule)
@section('page_title', $session->intitule)
@section('page_icon', 'fa-chalkboard-teacher')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('formation.sessions.index') }}">Sessions</a></li>
    <li>{{ Str::limit($session->intitule, 40) }}</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <button type="button" class="btn btn-primary js-ajouter-participant">
            <i class="fas fa-plus"></i> Ajouter un participant
        </button>
    @endcan
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Statut</div>
                <span class="badge bg-{{ $session->statut?->couleur() }} fs-6">
                    {{ $session->statut?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Participants</div>
                <div class="fw-bold fs-4">{{ $stats['total_participants'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Taux de présence</div>
                <div class="fw-bold fs-4 text-{{ $stats['taux_presence'] >= 80 ? 'success' : 'warning' }}">
                    {{ $stats['taux_presence'] }} %
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Satisfaction</div>
                <div class="fw-bold fs-4">
                    {{ $stats['satisfaction_moyenne'] !== null ? $stats['satisfaction_moyenne'] . ' / 5' : '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Plan</dt>
            <dd class="col-sm-9">{{ $session->planFormation?->intitule ?? '—' }}</dd>

            <dt class="col-sm-3">Prestataire</dt>
            <dd class="col-sm-9">{{ $session->prestataire ?? '—' }}</dd>

            <dt class="col-sm-3">Lieu</dt>
            <dd class="col-sm-9">{{ $session->localisation ?? '—' }}</dd>

            <dt class="col-sm-3">Période</dt>
            <dd class="col-sm-9">
                {{ $session->date_debut?->format('d/m/Y') }}
                <i class="fas fa-arrow-right mx-1"></i>
                {{ $session->date_fin?->format('d/m/Y') }}
                ({{ number_format($session->duree_heures, 1) }} h)
            </dd>

            <dt class="col-sm-3">Coût</dt>
            <dd class="col-sm-9">{{ number_format($session->cout_reel, 0, ',', ' ') }} FCFA</dd>

            @if($stats['score_avant_moyen'] !== null)
                <dt class="col-sm-3">Progression moyenne</dt>
                <dd class="col-sm-9">
                    {{ $stats['score_avant_moyen'] }} → {{ $stats['score_apres_moyen'] }}
                    ({{ $stats['progression_moyenne'] >= 0 ? '+' : '' }}{{ $stats['progression_moyenne'] }})
                </dd>
            @endif
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Participants</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Présence</th>
                    <th class="text-end">Score avant</th>
                    <th class="text-end">Score après</th>
                    <th class="text-end">Progression</th>
                    <th class="text-end">Satisfaction</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($session->participants as $p)
                    @php $progression = $p->progression(); @endphp
                    <tr>
                        <td>
                            <strong>{{ $p->salarie?->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $p->salarie?->matricule }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $p->statut_presence?->couleur() }}">
                                {{ $p->statut_presence?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">{{ $p->score_avant ?? '—' }}</td>
                        <td class="text-end">{{ $p->score_apres ?? '—' }}</td>
                        <td class="text-end {{ $progression === null ? '' : ($progression >= 0 ? 'text-success' : 'text-danger') }}">
                            {{ $progression !== null ? (($progression >= 0 ? '+' : '') . $progression) : '—' }}
                        </td>
                        <td class="text-end">{{ $p->note_satisfaction ?? '—' }}</td>
                        <td class="text-end">
                            @can('permission', 'formation.manage')
                                <button type="button" class="btn btn-sm btn-action js-evaluer-participant"
                                        data-id="{{ $p->id }}"
                                        data-salarie="{{ $p->salarie?->nom_complet }}"
                                        data-presence="{{ $p->statut_presence?->value }}"
                                        data-avant="{{ $p->score_avant }}"
                                        data-apres="{{ $p->score_apres }}"
                                        data-satisfaction="{{ $p->note_satisfaction }}"
                                        data-commentaire="{{ $p->commentaire_evaluation }}"
                                        data-attestation="{{ $p->reference_attestation }}">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('formation.participants.destroy', [$session, $p]) }}"
                                      class="d-inline form-confirm-delete"
                                      data-confirm-title="Retirer ce participant ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucun participant inscrit.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('formation.sessions.partials._modal-participant')
@include('formation.sessions.partials._modal-evaluer')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-ajouter-participant', () => {
        $('#form-participant')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-participant')).show();
    });

    $(document).on('click', '.js-evaluer-participant', function () {
        const $btn = $(this);
        const id = $btn.data('id');

        $('#titre-modal-evaluer').text('Évaluer : ' + $btn.data('salarie'));
        $('#form-evaluer').attr('action',
            "{{ url('/formation/sessions/' . $session->id . '/participants/__ID__/evaluer') }}".replace('__ID__', id));

        $('#statut_presence').val($btn.data('presence'));
        $('#score_avant').val($btn.data('avant') ?? '');
        $('#score_apres').val($btn.data('apres') ?? '');
        $('#note_satisfaction').val($btn.data('satisfaction') ?? '');
        $('#commentaire_evaluation').val($btn.data('commentaire') ?? '');
        $('#reference_attestation').val($btn.data('attestation') ?? '');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-evaluer')).show();
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
                } else { window.showToast('Erreur.', 'error'); }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-participant', 'modal-participant', "{{ route('formation.sessions.participants.store', $session) }}");
});
</script>
@endpush