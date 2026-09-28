@extends('layouts.app')

@section('title', $plan->intitule)
@section('page_title', $plan->intitule . ' — ' . $plan->annee)
@section('page_icon', 'fa-calendar-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('formation.plans.index') }}">Plans</a></li>
    <li>{{ $plan->intitule }}</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <a href="{{ route('formation.sessions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle session
        </a>
    @endcan
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Budget initial</div>
                <div class="fw-bold fs-5">{{ number_format($etatBudget['budget_initial'], 0, ',', ' ') }}</div>
                <small class="text-muted">FCFA</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Consommé</div>
                <div class="fw-bold fs-5 text-danger">{{ number_format($etatBudget['consomme'], 0, ',', ' ') }}</div>
                <small class="text-muted">FCFA</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Restant</div>
                <div class="fw-bold fs-5 text-success">{{ number_format($etatBudget['restant'], 0, ',', ' ') }}</div>
                <small class="text-muted">FCFA</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Taux de consommation</div>
                <div class="fw-bold fs-5">{{ $etatBudget['taux_consommation'] }} %</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-{{ $etatBudget['taux_consommation'] > 90 ? 'danger' : 'primary' }}"
                         style="width: {{ min(100, $etatBudget['taux_consommation']) }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Sessions du plan</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Intitulé</th>
                    <th>Prestataire</th>
                    <th>Période</th>
                    <th>Durée</th>
                    <th class="text-end">Coût</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plan->sessions as $s)
                    <tr>
                        <td><strong>{{ $s->intitule }}</strong></td>
                        <td>{{ $s->prestataire ?? '—' }}</td>
                        <td>
                            {{ $s->date_debut?->format('d/m/Y') }}
                            <i class="fas fa-arrow-right mx-1"></i>
                            {{ $s->date_fin?->format('d/m/Y') }}
                        </td>
                        <td>{{ number_format($s->duree_heures, 1) }} h</td>
                        <td class="text-end">{{ number_format($s->cout_reel, 0, ',', ' ') }}</td>
                        <td>
                            <span class="badge bg-{{ $s->statut?->couleur() }}">
                                {{ $s->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('formation.sessions.show', $s) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucune session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection