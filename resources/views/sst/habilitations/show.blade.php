@extends('layouts.app')

@section('title', 'Habilitation')
@section('page_title', 'Habilitation — ' . $habilitation->intitule)
@section('page_icon', 'fa-certificate')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.habilitations.index') }}">Habilitations</a></li>
    <li>Détail</li>
@endsection

@section('page_actions')
    @if(in_array($habilitation->statut?->value, ['active', 'expired']))
        @can('permission', 'habilitations.manage')
            <button type="button" class="btn btn-success js-renouveler">
                <i class="fas fa-sync"></i> Renouveler
            </button>
            @if($habilitation->statut?->value === 'active')
                <button type="button" class="btn btn-outline-danger js-revoquer">
                    <i class="fas fa-ban"></i> Révoquer
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
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $habilitation->statut->couleur() }} fs-6">
                    {{ $habilitation->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Début</div>
                <div class="fw-bold">{{ $habilitation->date_debut?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Fin</div>
                <div class="fw-bold">{{ $habilitation->date_fin?->format('d/m/Y') ?? '—' }}</div>
                @if($habilitation->joursRestants() !== null && $habilitation->date_fin?->isFuture())
                    <small class="text-muted">J-{{ $habilitation->joursRestants() }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Renouvellements</div>
                <div class="fw-bold fs-4">{{ $habilitation->renouvellements->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Salarié</dt>
            <dd class="col-sm-9">{{ $habilitation->salarie?->nom_complet }}</dd>

            <dt class="col-sm-3">Catégorie</dt>
            <dd class="col-sm-9">{{ $habilitation->categorie }}</dd>

            <dt class="col-sm-3">Intitulé</dt>
            <dd class="col-sm-9">{{ $habilitation->intitule }}</dd>

            <dt class="col-sm-3">Portée</dt>
            <dd class="col-sm-9">{{ $habilitation->portee }}</dd>

            <dt class="col-sm-3">Risque lié</dt>
            <dd class="col-sm-9">{{ $habilitation->risque?->intitule ?? '—' }}</dd>

            <dt class="col-sm-3">Émetteur</dt>
            <dd class="col-sm-9">{{ $habilitation->emetteur ?? '—' }}</dd>

            <dt class="col-sm-3">Référence décision</dt>
            <dd class="col-sm-9">{{ $habilitation->reference_decision ?? '—' }}</dd>

            <dt class="col-sm-3">Référence formation</dt>
            <dd class="col-sm-9">{{ $habilitation->reference_formation ?? '—' }}</dd>

            <dt class="col-sm-3">Date décision</dt>
            <dd class="col-sm-9">{{ $habilitation->date_decision?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-3">Date revue</dt>
            <dd class="col-sm-9">{{ $habilitation->date_revue?->format('d/m/Y') ?? '—' }}</dd>

            @if($habilitation->motif)
                <dt class="col-sm-3 text-danger">Motif de révocation</dt>
                <dd class="col-sm-9 text-danger">{{ $habilitation->motif }}</dd>
            @endif

            @if($habilitation->habilitationOrigine)
                <dt class="col-sm-3">Habilitation d'origine</dt>
                <dd class="col-sm-9">
                    <a href="{{ route('sst.habilitations.show', $habilitation->habilitationOrigine) }}">
                        {{ $habilitation->habilitationOrigine->intitule }}
                        ({{ $habilitation->habilitationOrigine->date_debut?->format('d/m/Y') }})
                    </a>
                </dd>
            @endif
        </dl>
    </div>
</div>

@if($habilitation->renouvellements->count() > 0)
    <div class="card mt-3">
        <div class="card-header"><strong>Historique des renouvellements</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Émetteur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($habilitation->renouvellements as $r)
                        <tr>
                            <td>{{ $r->date_debut?->format('d/m/Y') }}</td>
                            <td>{{ $r->date_fin?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $r->statut->couleur() }}">
                                    {{ $r->statut->libelle() }}
                                </span>
                            </td>
                            <td>{{ $r->emetteur ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@include('sst.habilitations.partials._modal-renouveler')
@include('sst.habilitations.partials._modal-revoquer')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-renouveler', () => {
        $('#form-renouveler')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-renouveler')).show();
    });
    $(document).on('click', '.js-revoquer', () => {
        $('#form-revoquer')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-revoquer')).show();
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
                if (r.redirect) {
                    window.showToast(r.message || 'Renouvelée.');
                    setTimeout(() => { window.location.href = r.redirect; }, 600);
                } else {
                    window.showToastThenReload(r.message || 'Enregistré.');
                }
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
    }

    soumettreModal('#form-renouveler', 'modal-renouveler', "{{ route('sst.habilitations.renouveler', $habilitation) }}");
    soumettreModal('#form-revoquer', 'modal-revoquer', "{{ route('sst.habilitations.revoquer', $habilitation) }}");
});
</script>
@endpush