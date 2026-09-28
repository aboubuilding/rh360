@extends('layouts.app')

@section('title', 'Heures supplémentaires')
@section('page_title', 'Actes d\'heures supplémentaires')
@section('page_icon', 'fa-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Heures supplémentaires</li>
@endsection

@section('page_actions')
    @can('permission', 'paie.manage')
        <a href="{{ route('paie.heures-supp.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel acte
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Référence, salarié, matricule...">
            </div>
            <div class="col-md-3">
                <select name="periode_id" class="form-select">
                    <option value="">Toutes les périodes</option>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" @selected(request('periode_id') == $p->id)>{{ $p->libelle }}</option>
                    @endforeach
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
                    <th>Référence</th>
                    <th>Salarié</th>
                    <th>Période de travail</th>
                    <th>Période de paiement</th>
                    <th class="text-end">Total heures</th>
                    <th class="text-end">Montant</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actes as $a)
                    <tr>
                        <td><code>{{ $a->reference }}</code></td>
                        <td>
                            <strong>{{ $a->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $a->salarie?->matricule }}</small>
                        </td>
                        <td>
                            {{ $a->debut_travail?->format('d/m/Y') }}
                            <i class="fas fa-arrow-right mx-1"></i>
                            {{ $a->fin_travail?->format('d/m/Y') }}
                        </td>
                        <td>{{ $a->periodePaiement?->libelle }}</td>
                        <td class="text-end">{{ number_format($a->totalHeures(), 2) }} h</td>
                        <td class="text-end fw-bold">
                            {{ number_format($a->montantTotal(), 0, ',', ' ') }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('paie.heures-supp.show', $a) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('permission', 'paie.manage')
                                @if(! $a->periodePaiement?->estFigee())
                                    <form method="POST" action="{{ route('paie.heures-supp.destroy', $a) }}"
                                          class="d-inline form-confirm-delete"
                                          data-confirm-title="Supprimer cet acte ?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-action text-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun acte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $actes->links() }}</div>
</div>
@endsection