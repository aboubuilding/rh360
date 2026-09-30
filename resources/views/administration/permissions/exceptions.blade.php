@extends('layouts.app')

@section('title', 'Exceptions de permissions')
@section('page_title', 'Exceptions — ' . $utilisateur->nom_complet)
@section('page_icon', 'fa-key')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('admin.utilisateurs.index') }}">Utilisateurs</a></li>
    <li>Exceptions</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Les exceptions individuelles <strong>prévalent</strong> sur les permissions du rôle.
    Une permission accordée est ajoutée, une permission retirée est enlevée.
</div>

<form method="POST" action="{{ route('admin.permissions.exceptions.update', $utilisateur) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header"><strong>Permissions</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th class="text-center">Rôle</th>
                            <th class="text-center">Accorder</th>
                            <th class="text-center">Retirer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                            @php
                                $roleHasIt = \App\Domain\Administration\Models\PermissionRole::where('role', $utilisateur->role)
                                    ->where('permission', $permission)->where('autorise', true)->exists();
                                $exception = $exceptions->get($permission);
                            @endphp
                            <tr>
                                <td>{{ $permission }}</td>
                                <td class="text-center">
                                    @if($roleHasIt)
                                        <i class="fas fa-check text-success"></i>
                                    @else
                                        <i class="fas fa-times text-muted"></i>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="accorder[]" value="{{ $permission }}"
                                           aria-label="Accorder {{ $permission }}"
                                           @checked($exception && $exception->autorise)>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="retirer[]" value="{{ $permission }}"
                                           aria-label="Retirer {{ $permission }}"
                                           @checked($exception && ! $exception->autorise)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les exceptions</button>
            <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-secondary">Retour</a>
        </div>
    </div>
</form>
@endsection