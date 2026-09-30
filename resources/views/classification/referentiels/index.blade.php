@extends('layouts.app')

@section('title', 'Référentiels')
@section('page_title', 'Référentiels de classification')
@section('page_icon', 'fa-book')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Classification</li>
    <li>Référentiels</li>
@endsection

@section('page_actions')
    @can('permission', 'classification.manage')
        <a href="{{ route('classification.referentiels.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau référentiel
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Priorité</th>
                    <th>Effet</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($referentiels as $r)
                    <tr>
                        <td><code>{{ $r->code }}</code></td>
                        <td><a href="{{ route('classification.referentiels.show', $r) }}"><strong>{{ $r->nom }}</strong></a></td>
                        <td>{{ $r->type_referentiel }}</td>
                        <td>{{ $r->priorite }}</td>
                        <td>
                            @if($r->debut_effet)
                                {{ $r->debut_effet->format('d/m/Y') }}
                                @if($r->fin_effet) → {{ $r->fin_effet->format('d/m/Y') }} @endif
                            @else —
                            @endif
                        </td>
                        <td>
                            @if($r->estActif())
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('classification.referentiels.show', $r) }}"><i class="fas fa-eye"></i> Voir</a></li>
                                    @can('permission', 'classification.manage')
                                        <li><a class="dropdown-item" href="{{ route('classification.referentiels.edit', $r) }}"><i class="fas fa-edit"></i> Modifier</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('classification.referentiels.destroy', $r) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer ce référentiel ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun référentiel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $referentiels->links() }}</div>
</div>
@endsection