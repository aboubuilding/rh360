@extends('layouts.app')

@section('title', $structure->nom)
@section('page_title', $structure->nom)
@section('page_icon', 'fa-sitemap')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('organisation.structures.index') }}">Structures</a></li>
    <li>{{ $structure->nom }}</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><code>{{ $structure->code }}</code></dd>
            <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $structure->typeStructure?->nom ?? '—' }}</dd>
            <dt class="col-sm-3">Parent</dt><dd class="col-sm-9">{{ $structure->parent?->nom ?? '—' }}</dd>
            <dt class="col-sm-3">Localisation</dt><dd class="col-sm-9">{{ $structure->localisation ?? '—' }}</dd>
            <dt class="col-sm-3">Centre de coût</dt><dd class="col-sm-9">{{ $structure->centre_cout ?? '—' }}</dd>
            <dt class="col-sm-3">État</dt>
            <dd class="col-sm-9">
                @if($structure->estActif())
                    <span class="badge bg-success">Actif</span>
                @else
                    <span class="badge bg-secondary">Inactif</span>
                @endif
            </dd>
        </dl>
    </div>
</div>

@if($structure->enfants->count() > 0)
    <div class="card mb-3">
        <div class="card-header"><strong>Sous-structures</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Code</th><th>Nom</th><th>Type</th></tr></thead>
                <tbody>
                    @foreach($structure->enfants as $enfant)
                        <tr>
                            <td><code>{{ $enfant->code }}</code></td>
                            <td><a href="{{ route('organisation.structures.show', $enfant) }}">{{ $enfant->nom }}</a></td>
                            <td>{{ $enfant->typeStructure?->nom ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($structure->postes->count() > 0)
    <div class="card">
        <div class="card-header"><strong>Postes rattachés</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Code</th><th>Intitulé</th><th>Effectif cible</th></tr></thead>
                <tbody>
                    @foreach($structure->postes as $poste)
                        <tr>
                            <td><code>{{ $poste->code }}</code></td>
                            <td>{{ $poste->intitule }}</td>
                            <td>{{ $poste->effectif_cible ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection