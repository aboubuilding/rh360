@extends('layouts.app')

@section('title', 'Reporting carrière')
@section('page_title', 'Reporting carrière')
@section('page_icon', 'fa-chart-bar')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Reporting</li>
@endsection

@section('contenu')
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
                <label class="form-label small mb-1">Date retenue</label>
                <select name="base" class="form-select">
                    <option value="date_proposition" @selected($base === 'date_proposition')>Proposition</option>
                    <option value="date_decision" @selected($base === 'date_decision')>Décision</option>
                    <option value="date_acte" @selected($base === 'date_acte')>Acte</option>
                    <option value="date_effet" @selected($base === 'date_effet')>Effet</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">&nbsp;</label>
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Appliquer</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Total actes</div>
                <div class="fs-3 fw-bold">{{ $indicateurs['total_actes'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Salariés concernés</div>
                <div class="fs-3 fw-bold">{{ $indicateurs['salaries_distincts'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Types distincts</div>
                <div class="fs-3 fw-bold">{{ count($indicateurs['par_type']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Statuts distincts</div>
                <div class="fs-3 fw-bold">{{ count($indicateurs['par_statut']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Répartition par type</strong></div>
            <div class="card-body">
                @foreach($indicateurs['par_type'] as $type => $count)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $type }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Répartition par statut</strong></div>
            <div class="card-body">
                @foreach($indicateurs['par_statut'] as $statut => $count)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $statut }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-pills card-header-pills">
            <li class="nav-item">
                <a class="nav-link {{ $vue === 'actes' ? 'active' : '' }}"
                   href="{{ route('carriere.reporting.index', array_merge(request()->all(), ['vue' => 'actes'])) }}">
                    Actes passés
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $vue === 'situations_initiales' ? 'active' : '' }}"
                   href="{{ route('carriere.reporting.index', array_merge(request()->all(), ['vue' => 'situations_initiales'])) }}">
                    Situations initiales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $vue === 'entrees_fonction' ? 'active' : '' }}"
                   href="{{ route('carriere.reporting.index', array_merge(request()->all(), ['vue' => 'entrees_fonction'])) }}">
                    Entrées en fonction
                </a>
            </li>
        </ul>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Salarié</th>
                    <th>Type / Nature</th>
                    <th>Statut</th>
                    <th>Référence</th>
                </tr>
            </thead>
            <tbody>
                @forelse($historique as $item)
                    <tr>
                        <td>
                            {{ $item->date_effet?->format('d/m/Y')
                                ?? $item->updated_at?->format('d/m/Y')
                                ?? '—' }}
                        </td>
                        <td>
                            @if($item instanceof \App\Domain\Carriere\Models\MouvementCarriere)
                                {{ $item->salarie?->nom_complet ?? '—' }}
                            @else
                                {{ $item->salarie?->nom_complet ?? '—' }}
                            @endif
                        </td>
                        <td>
                            @if($item instanceof \App\Domain\Carriere\Models\MouvementCarriere)
                                {{ $item->type_mouvement->libelle() }}
                            @else
                                {{ $item->type_source?->libelle() ?? '—' }}
                            @endif
                        </td>
                        <td>
                            @if($item instanceof \App\Domain\Carriere\Models\MouvementCarriere)
                                <span class="badge bg-{{ $item->statut->couleur() }}">
                                    {{ $item->statut->libelle() }}
                                </span>
                            @else
                                <span class="badge bg-{{ $item->statut_fiabilite?->couleur() ?? 'secondary' }}">
                                    {{ $item->statut_fiabilite?->libelle() ?? '—' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($item instanceof \App\Domain\Carriere\Models\MouvementCarriere)
                                <code>{{ $item->numero_mouvement }}</code>
                            @else
                                {{ $item->reference_acte ?? '—' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucune donnée pour cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection