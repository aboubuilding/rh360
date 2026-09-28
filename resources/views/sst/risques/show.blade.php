@extends('layouts.app')

@section('title', $risque->intitule)
@section('page_title', 'Risque — ' . Str::limit($risque->intitule, 50))
@section('page_icon', 'fa-exclamation-triangle')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.risques.index') }}">Risques</a></li>
    <li>{{ Str::limit($risque->intitule, 30) }}</li>
@endsection

@section('page_actions')
    @if($risque->statut === 'active')
        @can('permission', 'risks.manage')
            <button type="button" class="btn btn-primary js-evaluer">
                <i class="fas fa-clipboard-check"></i> Nouvelle évaluation
            </button>
            <button type="button" class="btn btn-secondary js-ajouter-action">
                <i class="fas fa-plus"></i> Action de prévention
            </button>
            <button type="button" class="btn btn-outline-danger js-archiver">
                <i class="fas fa-archive"></i> Archiver
            </button>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Famille</div>
                <div class="fw-bold">{{ $risque->famille?->libelle() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Niveau actuel</div>
                @php $niveau = $risque->niveauDerniereEvaluation(); @endphp
                @if($niveau)
                    <span class="badge bg-{{ $niveau->couleur() }} fs-6">{{ $niveau->libelle() }}</span>
                @else
                    <span class="text-muted">Non évalué</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                @if($risque->statut === 'active')
                    <span class="badge bg-success fs-6">Actif</span>
                @else
                    <span class="badge bg-secondary fs-6">Archivé</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Prochaine revue</div>
                <div class="fw-bold">{{ $risque->date_echeance_revue?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-identite">
        <i class="fas fa-info-circle"></i> Identité</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-evaluations">
        <i class="fas fa-clipboard-check"></i> Évaluations ({{ $risque->evaluations->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-actions">
        <i class="fas fa-tasks"></i> Actions ({{ $risque->actions->count() }})</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-identite">
        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Site</dt>
                    <dd class="col-sm-9">{{ $risque->site }}</dd>

                    <dt class="col-sm-3">Poste</dt>
                    <dd class="col-sm-9">{{ $risque->poste?->intitule ?? '—' }}</dd>

                    <dt class="col-sm-3">Activité</dt>
                    <dd class="col-sm-9">{{ $risque->activite }}</dd>

                    <dt class="col-sm-3">Danger</dt>
                    <dd class="col-sm-9">{{ $risque->danger }}</dd>

                    <dt class="col-sm-3">Conséquences</dt>
                    <dd class="col-sm-9">{{ $risque->consequences }}</dd>

                    <dt class="col-sm-3">Responsable</dt>
                    <dd class="col-sm-9">{{ $risque->responsable?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-3">Identifié le</dt>
                    <dd class="col-sm-9">{{ $risque->date_identification?->format('d/m/Y') ?? '—' }}</dd>

                    @if($risque->motif_archivage)
                        <dt class="col-sm-3 text-danger">Motif archivage</dt>
                        <dd class="col-sm-9 text-danger">{{ $risque->motif_archivage }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-evaluations">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Motif</th>
                            <th>Gravité</th>
                            <th>Probabilité</th>
                            <th>Score</th>
                            <th>Niveau</th>
                            <th>Prochaine revue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($risque->evaluations as $e)
                            <tr>
                                <td>{{ $e->date_evaluation?->format('d/m/Y') }}</td>
                                <td>{{ ucfirst($e->motif) }}</td>
                                <td>{{ $e->gravite }}</td>
                                <td>{{ $e->probabilite }}</td>
                                <td><strong>{{ $e->score }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $e->niveau->couleur() }}">
                                        {{ $e->niveau->libelle() }}
                                    </span>
                                </td>
                                <td>{{ $e->date_prochaine_revue?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Aucune évaluation.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-actions">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Intitulé</th>
                            <th>Type de mesure</th>
                            <th>Responsable</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($risque->actions as $a)
                            <tr class="{{ $a->estEnRetard() ? 'table-warning' : '' }}">
                                <td>{{ $a->intitule }}</td>
                                <td>{{ $a->type_mesure?->libelle() }}</td>
                                <td>{{ $a->responsable?->nom_complet ?? '—' }}</td>
                                <td>{{ $a->date_echeance?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $a->statut->couleur() }}">
                                        {{ $a->statut->libelle() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Aucune action.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('sst.risques.partials._modal-evaluer')
@include('sst.risques.partials._modal-action')
@include('sst.risques.partials._modal-archiver')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-evaluer', () => {
        $('#form-evaluer')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-evaluer')).show();
    });
    $(document).on('click', '.js-ajouter-action', () => {
        $('#form-action')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-action')).show();
    });
    $(document).on('click', '.js-archiver', () => {
        $('#form-archiver')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-archiver')).show();
    });

    // Calcul du score en temps réel
    function majScore() {
        const g = parseInt($('#gravite').val() || 0);
        const p = parseInt($('#probabilite').val() || 0);
        const score = g * p;
        let niveau = '—';
        let couleur = 'secondary';

        if (score > 0) {
            if (score <= 4) { niveau = 'Faible'; couleur = 'success'; }
            else if (score <= 9) { niveau = 'Moyen'; couleur = 'warning'; }
            else if (score <= 16) { niveau = 'Élevé'; couleur = 'danger'; }
            else { niveau = 'Critique'; couleur = 'dark'; }
        }

        $('#apercu-score').html(
            '<span class="badge bg-' + couleur + ' fs-6">' +
            (score > 0 ? score + ' — ' + niveau : '—') +
            '</span>'
        );
    }

    $('#gravite, #probabilite').on('change keyup', majScore);

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
                } else {
                    window.showToast('Erreur.', 'error');
                }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-evaluer', 'modal-evaluer', "{{ route('sst.risques.evaluer', $risque) }}");
    soumettreModal('#form-action', 'modal-action', "{{ route('sst.risques.actions.store', $risque) }}");
    soumettreModal('#form-archiver', 'modal-archiver', "{{ route('sst.risques.archiver', $risque) }}");
});
</script>
@endpush