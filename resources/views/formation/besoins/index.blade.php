@extends('layouts.app')

@section('title', 'Besoins de formation')
@section('page_title', 'Besoins de formation')
@section('page_icon', 'fa-clipboard-list')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Formation</li>
    <li>Besoins</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <a href="{{ route('formation.besoins.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau besoin
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, motif...">
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
                <input type="number" name="annee" value="{{ request('annee') }}" class="form-control" placeholder="Année">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Intitulé</th>
                    <th>Priorité</th>
                    <th>Année cible</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($besoins as $b)
                    <tr>
                        <td>{{ $b->salarie?->nom_complet ?? '—' }}</td>
                        <td><strong>{{ $b->intitule }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $b->priorite?->couleur() }}">
                                {{ $b->priorite?->libelle() }}
                            </span>
                        </td>
                        <td>{{ $b->annee_cible }}</td>
                        <td>
                            <span class="badge bg-{{ $b->statut?->couleur() }}">
                                {{ $b->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($b->statut?->value === 'a_etudier')
                                @can('permission', 'formation.validate')
                                    <form method="POST" action="{{ route('formation.besoins.valider', $b) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-action" title="Valider">
                                            <i class="fas fa-check text-success"></i>
                                        </button>
                                    </form>
                                @endcan
                            @endif
                            @can('permission', 'formation.manage')
                                <form method="POST" action="{{ route('formation.besoins.destroy', $b) }}"
                                      class="d-inline form-confirm-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun besoin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $besoins->links() }}</div>
</div>
@endsection