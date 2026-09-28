@extends('layouts.app')

@section('title', 'Plans de formation')
@section('page_title', 'Plans de formation annuels')
@section('page_icon', 'fa-calendar-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Formation</li>
    <li>Plans annuels</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <a href="{{ route('formation.plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau plan
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="number" name="annee" value="{{ request('annee', now()->year) }}" class="form-control" placeholder="Année">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @forelse($plans as $p)
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $p->intitule }}</strong>
                    <span class="badge bg-primary">{{ $p->annee }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-6">Budget initial</dt>
                        <dd class="col-6 text-end">{{ number_format($p->montant_budget, 0, ',', ' ') }} FCFA</dd>

                        <dt class="col-6">Consommé</dt>
                        <dd class="col-6 text-end text-danger">{{ number_format($p->etat_budget['consomme'], 0, ',', ' ') }}</dd>

                        <dt class="col-6">Restant</dt>
                        <dd class="col-6 text-end fw-bold text-success">{{ number_format($p->etat_budget['restant'], 0, ',', ' ') }}</dd>

                        <dt class="col-6">Sessions</dt>
                        <dd class="col-6 text-end">{{ $p->etat_budget['nombre_sessions'] }}</dd>

                        <dt class="col-6">Taux conso.</dt>
                        <dd class="col-6 text-end">{{ $p->etat_budget['taux_consommation'] }} %</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('formation.plans.show', $p) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Consulter
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p class="mb-0">Aucun plan de formation.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $plans->links() }}</div>
@endsection