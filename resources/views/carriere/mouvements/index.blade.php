@extends('layouts.app')

@section('title', 'Mouvements de carrière')
@section('page_title', 'Actes de carrière')
@section('page_icon', 'fa-chart-line')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Actes de carrière</li>
@endsection

@section('page_actions')
    @can('permission', 'carriere.manage')
        <a href="{{ route('carriere.mouvements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel acte
        </a>
    @endcan
@endsection

@section('contenu')
<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $vue === 'tous' ? 'active' : '' }}"
           href="{{ route('carriere.mouvements.index', ['vue' => 'tous']) }}">
            <i class="fas fa-list"></i> Tous
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $vue === 'a_traiter' ? 'active' : '' }}"
           href="{{ route('carriere.mouvements.index', ['vue' => 'a_traiter']) }}">
            <i class="fas fa-tasks"></i> À traiter
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $vue === 'programmes' ? 'active' : '' }}"
           href="{{ route('carriere.mouvements.index', ['vue' => 'programmes']) }}">
            <i class="fas fa-calendar-alt"></i> Programmés
        </a>
    </li>
</ul>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <input type="hidden" name="vue" value="{{ $vue }}">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Numéro, salarié, matricule...">
            </div>
            <div class="col-md-2">
                <select name="type_mouvement" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $val => $lib)
                        <option value="{{ $val }}" @selected(request('type_mouvement') === $val)>{{ $lib }}</option>
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
                    <th>Départ → Cible</th>
                    <th>Date d'effet</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mouvements as $m)
                    <tr>
                        <td><code>{{ $m->numero_mouvement }}</code></td>
                        <td>
                            <strong>{{ $m->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $m->salarie?->matricule ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $m->type_mouvement->couleur() }}">
                                {{ $m->type_mouvement->libelle() }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $m->posteDepart?->intitule ?? '—' }}</small>
                            <i class="fas fa-arrow-right mx-1 text-muted"></i>
                            <strong>{{ $m->posteCible?->intitule ?? '—' }}</strong>
                        </td>
                        <td>{{ $m->date_effet?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $m->statut->couleur() }}">
                                {{ $m->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('carriere.mouvements.show', $m) }}">
                                            <i class="fas fa-eye"></i> Voir la fiche
                                        </a>
                                    </li>
                                    @can('permission', 'carriere.manage')
                                        @if($m->statut->estModifiable())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('carriere.mouvements.edit', $m) }}">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </a>
                                            </li>
                                        @endif
                                    @endcan
                                    @if($m->statut->value === 'draft')
                                        @can('permission', 'carriere.manage')
                                            <li>
                                                <form method="POST" action="{{ route('carriere.mouvements.soumettre', $m) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-paper-plane"></i> Soumettre
                                                    </button>
                                                </form>
                                            </li>
                                        @endcan
                                    @endif
                                    @can('permission', 'carriere.manage')
                                        @if($m->statut->value === 'draft')
                                            <li>
                                                <form method="POST" action="{{ route('carriere.mouvements.destroy', $m) }}"
                                                      class="form-confirm-delete"
                                                      data-confirm-title="Supprimer ce mouvement ?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $mouvements->links() }}</div>
</div>
@endsection