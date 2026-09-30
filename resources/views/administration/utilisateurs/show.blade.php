@extends('layouts.app')

@section('title', $utilisateur->nom_complet)
@section('page_title', $utilisateur->nom_complet)
@section('page_icon', 'fa-user-cog')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('admin.utilisateurs.index') }}">Utilisateurs</a></li>
    <li>{{ $utilisateur->nom_complet }}</li>
@endsection

@section('page_actions')
    @can('permission', 'admin.utilisateurs.manage')
        <a href="{{ route('admin.utilisateurs.edit', $utilisateur) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Nom complet</dt><dd class="col-sm-9">{{ $utilisateur->nom_complet }}</dd>
            <dt class="col-sm-3">Identifiant</dt><dd class="col-sm-9">{{ $utilisateur->identifiant }}</dd>
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $utilisateur->email ?? '—' }}</dd>
            <dt class="col-sm-3">Rôle</dt><dd class="col-sm-9"><span class="badge bg-primary">{{ $utilisateur->libelleRole() }}</span></dd>
            <dt class="col-sm-3">État</dt>
            <dd class="col-sm-9">
                @if($utilisateur->etat === \App\Domain\Shared\Enums\Etat::ACTIF)
                    <span class="badge bg-success">Actif</span>
                @elseif($utilisateur->etat === \App\Domain\Shared\Enums\Etat::INACTIF)
                    <span class="badge bg-secondary">Inactif</span>
                @else
                    <span class="badge bg-danger">Supprimé</span>
                @endif
            </dd>
            <dt class="col-sm-3">Dernière connexion</dt>
            <dd class="col-sm-9">{{ $utilisateur->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}</dd>
            <dt class="col-sm-3">Créé le</dt>
            <dd class="col-sm-9">{{ $utilisateur->created_at->format('d/m/Y H:i') }}</dd>
        </dl>
    </div>
</div>
@endsection