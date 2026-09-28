@extends('layouts.app')

@section('title', 'Campagnes d\'évaluation')
@section('page_title', 'Campagnes d\'évaluation')
@section('page_icon', 'fa-star')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Performance</li>
    <li>Campagnes</li>
@endsection

@section('page_actions')
    @can('permission', 'performance.manage')
        <a href="{{ route('performance.campagnes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle campagne
        </a>
    @endcan
@endsection

@section('contenu')
<div class="row">
    @forelse($campagnes as $c)
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $c->intitule }}</strong>
                    <span class="badge bg-{{ $c->statut?->couleur() }}">{{ $c->statut?->libelle() }}</span>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-1">Année {{ $c->annee }}</div>
                    <div class="small">
                        Du {{ $c->date_debut?->format('d/m/Y') }} au {{ $c->date_fin?->format('d/m/Y') }}
                    </div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $c->tauxRealisation() }}%"></div>
                    </div>
                    <div class="text-muted small mt-2">
                        Taux de réalisation : {{ $c->tauxRealisation() }} %
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('performance.campagnes.show', $c) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Consulter
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p class="mb-0">Aucune campagne d'évaluation.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection