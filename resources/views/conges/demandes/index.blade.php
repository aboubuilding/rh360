@extends('layouts.app')

@section('title', 'Demandes de congés')
@section('page_title', 'Demandes de congés & permissions')
@section('page_icon', 'fa-plane-departure')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Demandes</li>
@endsection

@section('page_actions')
    <a href="{{ route('conges.planning.index') }}" class="btn btn-secondary">
        <i class="fas fa-calendar-alt"></i> Planning annuel
    </a>
    @can('permission', 'conges.manage')
        <a href="{{ route('conges.demandes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle demande
        </a>
    @endcan
@endsection

@section('contenu')
<ul class="nav nav-pills mb-3">
    @php
        $vues = [
            'toutes' => ['Toutes', 'fa-list'],
            'a_traiter' => ['À traiter', 'fa-tasks'],
            'programmes' => ['Programmées', 'fa-calendar-check'],
            'en_cours' => ['En cours', 'fa-hourglass-half'],
            'reprises_retard' => ['Reprises en retard', 'fa-exclamation-triangle'],
        ];
    @endphp
    @foreach($vues as $key => [$libelle, $icone])
        <li class="nav-item">
            <a class="nav-link {{ $vue === $key ? 'active' : '' }}"
               href="{{ route('conges.demandes.index', ['vue' => $key]) }}">
                <i class="fas {{ $icone }}"></i> {{ $libelle }}
            </a>
        </li>
    @endforeach
</ul>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <input type="hidden" name="vue" value="{{ $vue }}">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Numéro, salarié...">
            </div>
            <div class="col-md-2">
                <select name="type_conge_id" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" @selected(request('type_conge_id') == $t->id)>{{ $t->nom }}</option>
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
                    <th>N°</th>
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Du</th>
                    <th>Au (reprise)</th>
                    <th>Durée</th>
                    <th>Remplaçant</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($demandes as $d)
                    <tr class="{{ $d->estEnRetard() ? 'table-warning' : '' }}">
                        <td><code>{{ $d->numero_demande }}</code></td>
                        <td>
                            <strong>{{ $d->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $d->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $d->typeConge?->nom ?? '—' }}</td>
                        <td>{{ $d->date_debut?->format('d/m/Y') }}</td>
                        <td>{{ $d->date_reprise?->format('d/m/Y') }}</td>
                        <td>{{ number_format($d->duree_jours, 2) }} j</td>
                        <td>{{ $d->remplacant ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $d->statut->couleur() }}">
                                {{ $d->statut->libelle() }}
                            </span>
                            @if($d->estEnRetard())
                                <span class="badge bg-danger">Reprise en retard</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('conges.demandes.show', $d) }}">
                                            <i class="fas fa-eye"></i> Détail
                                        </a>
                                    </li>
                                    @if($d->statut->value === 'authorized' || $d->statut->value === 'resumed')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('conges.demandes.acte', $d) }}" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Acte d'autorisation
                                            </a>
                                        </li>
                                    @endif
                                    @can('permission', 'conges.manage')
                                        @if($d->statut->estModifiable())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('conges.demandes.edit', $d) }}">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </a>
                                            </li>
                                        @endif
                                    @endcan
                                    @if($d->statut->value === 'draft')
                                        @can('permission', 'conges.manage')
                                            <li>
                                                <form method="POST" action="{{ route('conges.demandes.soumettre', $d) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-paper-plane"></i> Soumettre
                                                    </button>
                                                </form>
                                            </li>
                                        @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune demande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $demandes->links() }}</div>
</div>
@endsection