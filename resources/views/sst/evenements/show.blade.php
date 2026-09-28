@extends('layouts.app')

@section('title', $evenement->intitule)
@section('page_title', 'Événement sécurité')
@section('page_icon', 'fa-ambulance')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.evenements.index') }}">Événements</a></li>
    <li>{{ Str::limit($evenement->intitule, 40) }}</li>
@endsection

@section('page_actions')
    @if($evenement->estCloturable())
        @can('permission', 'safety.manage')
            <button type="button" class="btn btn-success js-cloturer">
                <i class="fas fa-flag-checkered"></i> Clôturer
            </button>
            <button type="button" class="btn btn-outline-danger js-annuler">
                <i class="fas fa-times"></i> Annuler
            </button>
        @endcan
    @endif
    @can('permission', 'safety.manage')
        <button type="button" class="btn btn-primary js-ajouter-action">
            <i class="fas fa-plus"></i> Action corrective
        </button>
    @endcan
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Type</div>
                <span class="badge bg-{{ $evenement->type_evenement->couleur() }} fs-6">
                    {{ $evenement->type_evenement->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $evenement->statut->couleur() }} fs-6">
                    {{ $evenement->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Survenance</div>
                <div class="fw-bold">{{ $evenement->date_survenance?->format('d/m/Y') }}</div>
                @if($evenement->heure_survenance)
                    <small class="text-muted">{{ $evenement->heure_survenance }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Participants</div>
                <div class="fw-bold fs-5">{{ $evenement->participants->count() }}</div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-details">
        <i class="fas fa-info-circle"></i> Détails</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-participants">
        <i class="fas fa-users"></i> Participants ({{ $evenement->participants->count() }})</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-actions">
        <i class="fas fa-tasks"></i> Actions ({{ $evenement->actions->count() }})</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-details">
        <div class="card">
            <div class="card-body">
                <h5>{{ $evenement->intitule }}</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Localisation</dt>
                    <dd class="col-sm-9">{{ $evenement->localisation }}</dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $evenement->description }}</dd>

                    @if($evenement->mesures_immediates)
                        <dt class="col-sm-3">Mesures immédiates</dt>
                        <dd class="col-sm-9">{{ $evenement->mesures_immediates }}</dd>
                    @endif

                    @if($evenement->analyse)
                        <dt class="col-sm-3">Analyse</dt>
                        <dd class="col-sm-9">{{ $evenement->analyse }}</dd>
                    @endif

                    @if($evenement->synthese_cloture)
                        <dt class="col-sm-3">Synthèse de clôture</dt>
                        <dd class="col-sm-9">{{ $evenement->synthese_cloture }}</dd>
                    @endif

                    <dt class="col-sm-3">Déclaré par</dt>
                    <dd class="col-sm-9">{{ $evenement->creePar?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-3">Déclaré le</dt>
                    <dd class="col-sm-9">{{ $evenement->created_at->format('d/m/Y H:i') }}</dd>

                    @if($evenement->date_cloture)
                        <dt class="col-sm-3">Clôturé le</dt>
                        <dd class="col-sm-9">{{ $evenement->date_cloture->format('d/m/Y') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-participants">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Matricule</th>
                            <th>Poste</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evenement->participants as $p)
                            <tr>
                                <td>{{ $p->nom_complet }}</td>
                                <td><code>{{ $p->matricule }}</code></td>
                                <td>{{ $p->affectationCourante?->poste?->intitule ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Aucun participant.</td></tr>
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
                            <th>Responsable</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evenement->actions as $a)
                            <tr class="{{ $a->estEnRetard() ? 'table-warning' : '' }}">
                                <td>{{ $a->intitule }}</td>
                                <td>{{ $a->responsable?->nom_complet ?? '—' }}</td>
                                <td>{{ $a->date_echeance?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $a->statut->couleur() }}">
                                        {{ $a->statut->libelle() }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($a->resultat, 60) ?? '—' }}</td>
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

@include('sst.evenements.partials._modal-cloturer')
@include('sst.evenements.partials._modal-annuler')
@include('sst.evenements.partials._modal-action')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-cloturer', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cloturer')).show();
    });
    $(document).on('click', '.js-annuler', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-annuler')).show();
    });
    $(document).on('click', '.js-ajouter-action', () => {
        $('#form-action')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-action')).show();
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
                } else {
                    window.showToast('Erreur.', 'error');
                }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-cloturer', 'modal-cloturer', "{{ route('sst.evenements.cloturer', $evenement) }}");
    soumettreModal('#form-annuler', 'modal-annuler', "{{ route('sst.evenements.annuler', $evenement) }}");
    soumettreModal('#form-action', 'modal-action', "{{ route('sst.evenements.actions.store', $evenement) }}");
});
</script>
@endpush