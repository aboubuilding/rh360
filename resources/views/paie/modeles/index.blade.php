@extends('layouts.app')

@section('title', 'Modèles de paie')
@section('page_title', 'Modèles de bulletin par catégorie')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Modèles</li>
@endsection

@section('page_actions')
    @can('permission', 'paie.manage')
        <a href="{{ route('paie.modeles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau modèle
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Rubriques rattachées</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modeles as $m)
                    <tr>
                        <td><strong>{{ $m->nom }}</strong></td>
                        <td>{{ $m->categorie?->libelle ?? '—' }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $m->rubriques->count() }} rubriques</span>
                        </td>
                        <td>
                            @if($m->estActif())
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('permission', 'paie.manage')
                                <a href="{{ route('paie.modeles.edit', $m) }}" class="btn btn-sm btn-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('paie.modeles.destroy', $m) }}"
                                      class="d-inline form-confirm-delete"
                                      data-confirm-title="Supprimer ce modèle ?">
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
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun modèle.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $modeles->links() }}</div>
</div>
@endsection