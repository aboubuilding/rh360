@extends('layouts.app')

@section('title', 'Notifications contractuelles')
@section('page_title', 'Boîte de réception — Notifications contractuelles')
@section('page_icon', 'fa-envelope')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Contrats</li>
    <li>Notifications</li>
@endsection

@section('page_actions')
    <form method="POST" action="{{ route('contrats.notifications.toutes-lues') }}" class="d-inline js-form-toutes-lues">
        @csrf
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-check-double"></i> Tout marquer comme lu
        </button>
    </form>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="statut" class="form-select">
                    <option value="">Toutes</option>
                    <option value="non_lues" @selected(request('statut') === 'non_lues')>Non lues</option>
                    <option value="lues" @selected(request('statut') === 'lues')>Lues</option>
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
                    <th style="width:40px;"></th>
                    <th>Contrat</th>
                    <th>Salarié</th>
                    <th>Intitulé alerte</th>
                    <th>Échéance</th>
                    <th>Reçue le</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                    <tr class="{{ $n->estLu() ? '' : 'table-light fw-bold' }}">
                        <td>
                            @if(! $n->estLu())
                                <span class="badge bg-primary">●</span>
                            @endif
                        </td>
                        <td>
                            @if($n->alerte?->contrat)
                                <a href="{{ route('contrats.contrats.show', $n->alerte->contrat) }}">
                                    <code>{{ $n->alerte->contrat->reference }}</code>
                                </a>
                            @else — @endif
                        </td>
                        <td>{{ $n->alerte?->contrat?->salarie?->nom_complet ?? '—' }}</td>
                        <td>{{ $n->alerte?->intitule ?? '—' }}</td>
                        <td>{{ $n->alerte?->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $n->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            @if(! $n->estLu())
                                <form method="POST"
                                      action="{{ route('contrats.notifications.lue', $n) }}"
                                      class="d-inline js-form-marquer-lue">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-action" title="Marquer comme lue">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune notification.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $notifications->links() }}</div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('submit', '.js-form-marquer-lue, .js-form-toutes-lues', function (e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) { window.showToastThenReload(r.message || 'Notification(s) mise(s) à jour.'); })
        .fail(function () { window.showToast('Erreur.', 'error'); });
    });
});
</script>
@endpush