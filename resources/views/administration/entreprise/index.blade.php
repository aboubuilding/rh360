@extends('layouts.app')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_icon', 'fa-users-cog')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Utilisateurs</li>
@endsection

@section('page_actions')
    @can('permission', 'admin.utilisateurs.manage')
        <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel utilisateur
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher nom, identifiant, email...">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">Tous les rôles</option>
                    @foreach(\App\Domain\Administration\Models\Utilisateur::roles() as $code => $libelle)
                        <option value="{{ $code }}" @selected(request('role') === $code)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="etat" class="form-select">
                    <option value="">Tous les états</option>
                    <option value="1" @selected(request('etat') === '1')>Actif</option>
                    <option value="0" @selected(request('etat') === '0')>Inactif</option>
                    <option value="-1" @selected(request('etat') === '-1')>Supprimé</option>
                </select>
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
                    <th>Nom complet</th>
                    <th>Identifiant</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>État</th>
                    <th>Dernière connexion</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($utilisateurs as $u)
                    <tr>
                        <td><strong>{{ $u->nom_complet }}</strong></td>
                        <td>{{ $u->identifiant }}</td>
                        <td>{{ $u->email ?? '—' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $u->libelleRole() }}</span>
                        </td>
                        <td>
                            @if($u->etat === \App\Domain\Shared\Enums\Etat::ACTIF)
                                <span class="badge bg-success">Actif</span>
                            @elseif($u->etat === \App\Domain\Shared\Enums\Etat::INACTIF)
                                <span class="badge bg-secondary">Inactif</span>
                            @else
                                <span class="badge bg-danger">Supprimé</span>
                            @endif
                        </td>
                        <td>{{ $u->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.utilisateurs.show', $u) }}"><i class="fas fa-eye"></i> Voir</a></li>
                                    @can('permission', 'admin.utilisateurs.manage')
                                        <li><a class="dropdown-item" href="{{ route('admin.utilisateurs.edit', $u) }}"><i class="fas fa-edit"></i> Modifier</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.utilisateurs.toggle-actif', $u) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-{{ $u->actif ? 'ban' : 'check' }}"></i>
                                                    {{ $u->actif ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                    @can('permission', 'admin.permissions.manage')
                                        <li><a class="dropdown-item" href="{{ route('admin.permissions.exceptions', $u) }}"><i class="fas fa-key"></i> Exceptions</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $utilisateurs->links() }}
    </div>
</div>
@endsection