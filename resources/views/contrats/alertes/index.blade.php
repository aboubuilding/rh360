@extends('layouts.app')

@section('title', 'Alertes contractuelles')
@section('page_title', 'Alertes contractuelles')
@section('page_icon', 'fa-bell')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Contrats</li>
    <li>Alertes</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Rechercher par intitulé ou salarié...">
            </div>
            <div class="col-md-3">
                <select name="en_cours" class="form-select">
                    <option value="">Toutes</option>
                    <option value="1" @selected(request('en_cours') === '1')>En cours uniquement</option>
                    <option value="0" @selected(request('en_cours') === '0')>Clôturées</option>
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
                    <th>Intitulé</th>
                    <th>Contrat</th>
                    <th>Salarié</th>
                    <th>Échéance</th>
                    <th>Jours</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alertes as $a)
                    <tr>
                        <td>{{ $a->intitule }}</td>
                        <td>
                            @if($a->contrat)
                                <a href="{{ route('contrats.contrats.show', $a->contrat) }}">
                                    <code>{{ $a->contrat->reference }}</code>
                                </a>
                            @else — @endif
                        </td>
                        <td>{{ $a->contrat?->salarie?->nom_complet ?? '—' }}</td>
                        <td>{{ $a->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($a->en_cours)
                                @if($a->estEchue())
                                    <span class="badge bg-danger">Échue</span>
                                @else
                                    <span class="badge bg-info">J-{{ $a->joursRestants() }}</span>
                                @endif
                            @else — @endif
                        </td>
                        <td>
                            @if($a->en_cours)
                                <span class="badge bg-primary">En cours</span>
                            @else
                                <span class="badge bg-secondary">Clôturée</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($a->en_cours)
                                @can('permission', 'contrats.manage')
                                    <button type="button" class="btn btn-sm btn-action js-cloturer"
                                            data-id="{{ $a->id }}"
                                            data-intitule="{{ $a->intitule }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune alerte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $alertes->links() }}</div>
</div>

@include('contrats.alertes._modal-cloturer')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-cloturer', function () {
        const id = $(this).data('id');
        const intitule = $(this).data('intitule');
        $('#titre-modal-cloturer').text('Clôturer : ' + intitule);
        $('#form-cloturer').attr('action', "{{ url('/contrats/alertes/__ID__/cloturer') }}".replace('__ID__', id));
        $('#form-cloturer')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cloturer')).show();
    });

    $('#form-cloturer').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-cloturer')).hide();
            window.showToastThenReload(r.message || 'Alerte clôturée.');
        })
        .fail(function () { window.showToast('Erreur.', 'error'); })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush