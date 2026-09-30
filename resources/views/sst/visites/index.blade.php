@extends('layouts.app')

@section('title', 'Visites médicales')
@section('page_title', 'Visites médicales')
@section('page_icon', 'fa-stethoscope')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>Visites médicales</li>
@endsection

@section('page_actions')
    <a href="{{ route('sst.reporting.index') }}" class="btn btn-secondary">
        <i class="fas fa-chart-bar"></i> Reporting
    </a>
    @can('permission', 'health.manage')
        <a href="{{ route('sst.visites.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Programmer une visite
        </a>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-lock"></i>
    <strong>Registre confidentiel.</strong> Accès nominatif réservé aux utilisateurs disposant de la permission
    <code>sensitive.social_health</code>. Le reporting agrégé anonymisé est accessible séparément.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, matricule...">
            </div>
            <div class="col-md-2">
                <select name="type_visite" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $val => $lib)
                        <option value="{{ $val }}" @selected(request('type_visite') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="echeance" class="form-select">
                    <option value="">Toutes échéances</option>
                    <option value="proches" @selected(request('echeance') === 'proches')>Proches (30 j)</option>
                    <option value="echues" @selected(request('echeance') === 'echues')>Échues</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Date prévue</th>
                    <th>Date réalisée</th>
                    <th>Aptitude</th>
                    <th>Prochaine échéance</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visites as $v)
                    <tr class="{{ $v->estEchue() ? 'table-warning' : '' }}">
                        <td>
                            <strong>{{ $v->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $v->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $v->type_visite?->libelle() }}</td>
                        <td>{{ $v->date_prevue?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $v->date_realisation?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($v->aptitude)
                                <span class="badge bg-{{ $v->aptitude->couleur() }}">
                                    {{ $v->aptitude->libelle() }}
                                </span>
                            @endif
                        </td>
                        <td>{{ $v->date_prochaine_echeance?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $v->statut->couleur() }}">
                                {{ $v->statut->libelle() }}
                            </span>
                            @if($v->estEchue())
                                <span class="badge bg-danger">Échue</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown"
                                        aria-label="Actions sur la visite de {{ $v->salarie?->nom_complet }} du {{ $v->date_prevue?->format('d/m/Y') }}">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('sst.visites.show', $v) }}">
                                            <i class="fas fa-eye"></i> Détail
                                        </a>
                                    </li>
                                    @can('permission', 'health.manage')
                                        @if($v->statut?->value === 'planned')
                                            <li>
                                                <button type="button" class="dropdown-item js-renseigner"
                                                        data-id="{{ $v->id }}"
                                                        data-salarie="{{ $v->salarie?->nom_complet }}"
                                                        data-type="{{ $v->type_visite?->libelle() }}">
                                                    <i class="fas fa-check"></i> Renseigner
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item js-annuler"
                                                        data-id="{{ $v->id }}"
                                                        data-salarie="{{ $v->salarie?->nom_complet }}">
                                                    <i class="fas fa-times"></i> Annuler
                                                </button>
                                            </li>
                                        @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune visite.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $visites->links() }}</div>
</div>

@include('sst.visites.partials._modal-renseigner')
@include('sst.visites.partials._modal-annuler')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-renseigner', function () {
        const id = $(this).data('id');
        const salarie = $(this).data('salarie');
        const type = $(this).data('type');

        $('#titre-modal-renseigner').text('Renseigner la visite — ' + salarie);
        $('#sous-titre-visite').text(type);
        $('#form-renseigner').attr('action', "{{ url('/sst/visites/__ID__/renseigner') }}".replace('__ID__', id));
        $('#form-renseigner')[0].reset();
        $('#form-renseigner').find('.is-invalid').removeClass('is-invalid');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-renseigner')).show();
    });

    $(document).on('click', '.js-annuler', function () {
        const id = $(this).data('id');
        const salarie = $(this).data('salarie');

        $('#titre-modal-annuler').text('Annuler la visite — ' + salarie);
        $('#form-annuler').attr('action', "{{ url('/sst/visites/__ID__/annuler') }}".replace('__ID__', id));
        $('#form-annuler')[0].reset();
        $('#form-annuler').find('.is-invalid').removeClass('is-invalid');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-annuler')).show();
    });

    function soumettreModal(formSelector, modalId) {
        $(formSelector).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

            $.ajax({
                url: $form.attr('action'),
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
                    window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    soumettreModal('#form-renseigner', 'modal-renseigner');
    soumettreModal('#form-annuler', 'modal-annuler');
});
</script>
@endpush