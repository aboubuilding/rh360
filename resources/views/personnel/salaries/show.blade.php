@extends('layouts.app')

@section('title', $salarie->nom_complet)
@section('page_title', $salarie->nom_complet)
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li>{{ $salarie->nom_complet }}</li>
@endsection

@section('page_actions')
    @can('permission', 'salaries.manage')
        <a href="{{ route('personnel.salaries.edit', $salarie) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
    @endcan
    <a href="{{ route('personnel.salaries.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
@endsection

@section('contenu')
@if(!empty($champsManquants))
    <div class="alert alert-warning">
        <strong><i class="fas fa-exclamation-triangle"></i> Dossier incomplet.</strong>
        Champs manquants :
        <span class="badge bg-light text-dark">{{ implode(' · ', $champsManquants) }}</span>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($salarie->chemin_photo)
                    <img src="{{ route('personnel.salaries.photo', $salarie) }}"
                         class="rounded-circle mb-2"
                         style="width:120px;height:120px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-2"
                         style="width:120px;height:120px;font-size:2.5rem;">
                        {{ $salarie->initiales }}
                    </div>
                @endif
                <h4>{{ $salarie->nom_complet }}</h4>
                <p class="text-muted mb-1"><code>{{ $salarie->matricule }}</code></p>
                <span class="badge bg-primary">{{ $salarie->statut_emploi?->value ?? '—' }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Date d'embauche</div>
                        <div class="fw-bold">{{ $salarie->date_embauche?->format('d/m/Y') ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Type de contrat</div>
                        <div class="fw-bold">{{ $salarie->type_contrat ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Statut dossier</div>
                        <div class="fw-bold">
                            @if($salarie->statut_dossier === \App\Domain\Personnel\Enums\StatutDossier::COMPLET)
                                <span class="badge bg-success">Complet</span>
                            @else
                                <span class="badge bg-warning text-dark">À compléter</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-identite">
            <i class="fas fa-id-card"></i> Identité
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-coordonnees">
            <i class="fas fa-map-marker-alt"></i> Coordonnées
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-famille">
            <i class="fas fa-users"></i> Famille
        </a>
    </li>
    @can('permission', 'sensitive.social_health')
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-social">
                <i class="fas fa-shield-alt"></i> Social & santé
            </a>
        </li>
    @endcan
    @can('permission', 'sensitive.banking')
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-bancaire">
                <i class="fas fa-university"></i> Bancaire
            </a>
        </li>
    @endcan
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-affectations">
            <i class="fas fa-sitemap"></i> Affectations
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-documents">
            <i class="fas fa-folder-open"></i> Documents
            <span class="badge bg-secondary">{{ $salarie->documents->count() }}</span>
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-identite">
        @include('personnel.salaries.partials._identite')
    </div>
    <div class="tab-pane fade" id="tab-coordonnees">
        @include('personnel.salaries.partials._coordonnees')
    </div>
    <div class="tab-pane fade" id="tab-famille">
        @include('personnel.salaries.partials._famille')
    </div>
    @can('permission', 'sensitive.social_health')
        <div class="tab-pane fade" id="tab-social">
            @include('personnel.salaries.partials._social')
        </div>
    @endcan
    @can('permission', 'sensitive.banking')
        <div class="tab-pane fade" id="tab-bancaire">
            @include('personnel.salaries.partials._bancaire')
        </div>
    @endcan
    <div class="tab-pane fade" id="tab-affectations">
        @include('personnel.salaries.partials._affectations')
    </div>
    <div class="tab-pane fade" id="tab-documents">
        @include('personnel.salaries.partials._documents')
    </div>
</div>

@include('personnel.salaries.partials._modal-membre-foyer', ['salarie' => $salarie])
@include('personnel.salaries.partials._modal-document', ['salarie' => $salarie])
@endsection