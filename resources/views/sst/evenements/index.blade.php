@extends('layouts.app')

@section('title', 'Accidents & incidents')
@section('page_title', 'Accidents, incidents & presque-accidents')
@section('page_icon', 'fa-ambulance')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>Accidents & incidents</li>
@endsection

@section('page_actions')
    <a href="{{ route('sst.reporting.index') }}" class="btn btn-secondary">
        <i class="fas fa-chart-bar"></i> Reporting
    </a>
    @can('permission', 'safety.manage')
        <a href="{{ route('sst.evenements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Déclarer un événement
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, lieu, description...">
            </div>
            <div class="col-md-2">
                <select name="type_evenement" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $val => $lib)
                        <option value="{{ $val }}" @selected(request('type_evenement') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="du" value="{{ request('du') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="date" name="au" value="{{ request('au') }}" class="form-control">
            </div>
            <div class="col-md-1">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Intitulé</th>
                    <th>Localisation</th>
                    <th>Participants</th>
                    <th>Priorité</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evenements as $e)
                    <tr class="{{ $e->aDesActionsEnRetard() ? 'table-warning' : '' }}">
                        <td>
                            {{ $e->date_survenance?->format('d/m/Y') }}
                            @if($e->heure_survenance)
                                <br><small class="text-muted">{{ $e->heure_survenance }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $e->type_evenement->couleur() }}">
                                {{ $e->type_evenement->libelle() }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $e->intitule }}</strong>
                        </td>
                        <td>{{ $e->localisation }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $e->participants->count() }}</span>
                        </td>
                        <td>
                            @php
                                $priorites = [
                                    'low' => ['Faible', 'secondary'],
                                    'normal' => ['Normale', 'info'],
                                    'high' => ['Haute', 'warning'],
                                    'critical' => ['Critique', 'danger'],
                                ];
                                [$libelle, $couleur] = $priorites[$e->priorite] ?? ['—', 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $couleur }}">{{ $libelle }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $e->statut->couleur() }}">
                                {{ $e->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sst.evenements.show', $e) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun événement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $evenements->links() }}</div>
</div>
@endsection