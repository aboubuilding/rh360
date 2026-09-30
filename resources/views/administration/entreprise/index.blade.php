@extends('layouts.app')

@section('title', 'Entreprise')
@section('page_title', 'Fiche entreprise')
@section('page_icon', 'fa-building')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Entreprise</li>
@endsection

@section('page_actions')
    @can('permission', 'admin.entreprise.manage')
        <a href="{{ route('admin.entreprise.edit') }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center">
                @if($entreprise->chemin_logo)
                    <img src="{{ route('admin.entreprise.logo') }}" alt="Logo" class="img-fluid mb-3" style="max-height:120px;">
                @else
                    <div class="text-muted"><i class="fas fa-image fa-4x"></i><br>Pas de logo</div>
                @endif
                <h4>{{ $entreprise->nom }}</h4>
                <p class="text-muted">{{ $entreprise->sigle }}</p>
                <span class="badge bg-{{ $entreprise->estActif() ? 'success' : 'secondary' }}">
                    {{ $entreprise->etat_libelle }}
                </span>
            </div>
            <div class="col-md-8">
                <table class="table table-sm">
                    <tr><th>Forme juridique</th><td>{{ $entreprise->forme_juridique ?? '—' }}</td></tr>
                    <tr><th>NIF</th><td>{{ $entreprise->nif ?? '—' }}</td></tr>
                    <tr><th>N° employeur CNSS</th><td>{{ $entreprise->numero_employeur_cnss ?? '—' }}</td></tr>
                    <tr><th>Secteur</th><td>{{ $entreprise->secteur ?? '—' }}</td></tr>
                    <tr><th>Adresse</th><td>{{ $entreprise->adresse ?? '—' }}</td></tr>
                    <tr><th>Ville / Pays</th><td>{{ $entreprise->ville }} / {{ $entreprise->pays }}</td></tr>
                    <tr><th>Téléphone</th><td>{{ $entreprise->telephone ?? '—' }}</td></tr>
                    <tr><th>Email</th><td>{{ $entreprise->email ?? '—' }}</td></tr>
                    <tr><th>Devise</th><td>{{ $entreprise->devise }}</td></tr>
                    <tr><th>Date de bascule</th><td>{{ $entreprise->date_bascule?->format('d/m/Y') ?? '—' }}</td></tr>
                    <tr><th>Signataire</th><td>{{ $entreprise->nom_signataire ?? '—' }} — {{ $entreprise->fonction_signataire ?? '—' }}</td></tr>
                    <tr><th>Lieu de signature</th><td>{{ $entreprise->lieu_signature ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection