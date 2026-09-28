@extends('layouts.app')

@section('title', 'Reporting SST')
@section('page_title', 'Reporting Santé & Sécurité')
@section('page_icon', 'fa-chart-bar')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>Reporting</li>
@endsection

@section('page_actions')
    <a href="{{ route('sst.reporting.exporter', ['du' => $du, 'au' => $au]) }}"
       class="btn btn-secondary">
        <i class="fas fa-file-excel"></i> Exporter Excel
    </a>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-shield-alt"></i>
    <strong>Reporting anonymisé.</strong>
    Ce tableau de bord n'expose aucune donnée nominative ni avis médical. Seuls des indicateurs agrégés sont affichés.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Du</label>
                <input type="date" name="du" value="{{ $du }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Au</label>
                <input type="date" name="au" value="{{ $au }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">&nbsp;</label>
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Appliquer</button>
            </div>
        </form>
    </div>
</div>

<!-- Visites médicales -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Visites planifiées</div>
                <div class="fs-3 fw-bold">{{ $stats['visites_medicales']['total_planifiees'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Visites réalisées</div>
                <div class="fs-3 fw-bold text-success">{{ $stats['visites_medicales']['realisees'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Taux de réalisation</div>
                <div class="fs-3 fw-bold text-primary">
                    {{ $stats['visites_medicales']['taux_realisation'] }} %
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Événements sécurité</div>
                <div class="fs-3 fw-bold text-danger">{{ $stats['evenements_securite']['total'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <!-- Répartition aptitudes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Répartition des aptitudes</strong></div>
            <div class="card-body">
                @forelse($stats['visites_medicales']['repartition_aptitudes'] as $aptitude => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span>
                            @switch($aptitude)
                                @case('fit') Apte @break
                                @case('fit_with_restrictions') Apte avec restrictions @break
                                @case('temporarily_unfit') Inapte temporaire @break
                                @case('permanently_unfit') Inapte définitif @break
                                @case('pending') En attente @break
                                @default {{ $aptitude }}
                            @endswitch
                        </span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Événements par type -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Événements par type</strong></div>
            <div class="card-body">
                @forelse($stats['evenements_securite']['par_type'] as $type => $total)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucun événement.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <!-- Risques -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Risques actifs</strong></div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="fs-2 fw-bold text-warning">{{ $stats['risques']['total_actifs'] }}</div>
                    <small class="text-muted">risques identifiés</small>
                </div>
                @foreach($stats['risques']['par_famille'] as $famille => $total)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">{{ ucfirst($famille) }}</span>
                        <strong>{{ $total }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- EPI -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Équipements EPI</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Dotations actives</span>
                    <strong class="text-success">{{ $stats['epi']['total_actives'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>À remplacer</span>
                    <strong class="text-warning">{{ $stats['epi']['a_remplacer'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Expirées</span>
                    <strong class="text-danger">{{ $stats['epi']['expirees'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Habilitations -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Habilitations</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Actives</span>
                    <strong class="text-success">{{ $stats['habilitations']['total_actives'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Expirent sous 60 jours</span>
                    <strong class="text-warning">{{ $stats['habilitations']['expirent_60j'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Expirées</span>
                    <strong class="text-danger">{{ $stats['habilitations']['expirees'] }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection