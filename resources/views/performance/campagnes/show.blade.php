@extends('layouts.app')

@section('title', $campagne->intitule)
@section('page_title', $campagne->intitule)
@section('page_icon', 'fa-star')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('performance.campagnes.index') }}">Campagnes</a></li>
    <li>{{ $campagne->intitule }}</li>
@endsection

@section('page_actions')
    @if($campagne->statut?->value === 'en_cours')
        @can('permission', 'performance.manage')
            <form method="POST" action="{{ route('performance.campagnes.cloturer', $campagne) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-flag-checkered"></i> Clôturer
                </button>
            </form>
        @endcan
    @endif
    @if($campagne->statut?->value === 'cloturee')
        @can('permission', 'performance.manage')
            <form method="POST" action="{{ route('performance.campagnes.archiver', $campagne) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-archive"></i> Archiver
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
                <span class="badge bg-{{ $campagne->statut?->couleur() }} fs-6">
                    {{ $campagne->statut?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Entretiens</div>
                <div class="fw-bold fs-4">{{ $statistiques['total_entretiens'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Validés</div>
                <div class="fw-bold fs-4 text-success">{{ $statistiques['valides'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Note moyenne</div>
                <div class="fw-bold fs-4">{{ $statistiques['note_moyenne'] ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Répartition par appréciation</strong></div>
            <div class="card-body">
                @foreach($statistiques['appreciations'] as $libelle => $total)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $libelle }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Critères évalués</strong></div>
            <div class="card-body">
                @forelse($campagne->criteres as $c)
                    <div class="d-flex justify-content-between mb-1 small">
                        <span>{{ $c->libelle }} ({{ $c->famille?->libelle() }})</span>
                        <span>×{{ $c->ponderation }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0 small">Aucun critère défini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Entretiens de la campagne</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Statut</th>
                    <th class="text-end">Note auto</th>
                    <th class="text-end">Note manager</th>
                    <th class="text-end">Note finale</th>
                    <th>Date entretien</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campagne->entretiens as $e)
                    <tr>
                        <td><strong>{{ $e->salarie?->nom_complet }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $e->statut?->couleur() }}">
                                {{ $e->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">{{ $e->note_auto_evaluation ?? '—' }}</td>
                        <td class="text-end">{{ $e->note_manager ?? '—' }}</td>
                        <td class="text-end fw-bold">{{ $e->note_finale ?? '—' }}</td>
                        <td>{{ $e->date_entretien?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('performance.entretiens.show', $e) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucun entretien.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection