@extends('layouts.app')

@section('title', 'Détail de l\'entrée')
@section('page_title', 'Détail de l\'entrée d\'audit')
@section('page_icon', 'fa-history')

@section('contenu')
<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ $entree->created_at->format('d/m/Y H:i:s') }}</dd>
            <dt class="col-sm-3">Utilisateur</dt><dd class="col-sm-9">{{ $entree->utilisateur?->nom_complet ?? '—' }}</dd>
            <dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ $entree->action }}</dd>
            <dt class="col-sm-3">Entité</dt><dd class="col-sm-9">{{ $entree->entite }} #{{ $entree->entite_id }}</dd>
        </dl>

        @if($entree->details)
            <h5 class="mt-3">Détails</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode($entree->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif

        <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</div>
@endsection