@extends('layouts.app')

@section('title', $referentiel->nom)
@section('page_title', $referentiel->nom)
@section('page_icon', 'fa-book')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('classification.referentiels.index') }}">Référentiels</a></li>
    <li>{{ $referentiel->nom }}</li>
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center"><div class="card-body">
        <div class="text-muted small">Catégories</div><div class="fs-3 fw-bold">{{ $referentiel->categories->count() }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body">
        <div class="text-muted small">Classes</div><div class="fs-3 fw-bold">{{ $referentiel->classes->count() }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body">
        <div class="text-muted small">Échelons</div><div class="fs-3 fw-bold">{{ $referentiel->echelons->count() }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body">
        <div class="text-muted small">Positions</div><div class="fs-3 fw-bold">{{ $referentiel->positions->count() }}</div>
    </div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><code>{{ $referentiel->code }}</code></dd>
            <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $referentiel->type_referentiel }}</dd>
            <dt class="col-sm-3">Niveau source</dt><dd class="col-sm-9">{{ $referentiel->niveau_source }}</dd>
            <dt class="col-sm-3">Priorité</dt><dd class="col-sm-9">{{ $referentiel->priorite }}</dd>
            <dt class="col-sm-3">Portée</dt><dd class="col-sm-9">{{ $referentiel->portee ?? '—' }}</dd>
            <dt class="col-sm-3">Effet</dt>
            <dd class="col-sm-9">
                {{ $referentiel->debut_effet?->format('d/m/Y') ?? '—' }}
                @if($referentiel->fin_effet) → {{ $referentiel->fin_effet->format('d/m/Y') }} @endif
            </dd>
        </dl>
    </div>
</div>
@endsection