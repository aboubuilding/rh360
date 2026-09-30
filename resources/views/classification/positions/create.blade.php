@extends('layouts.app')

@section('title', 'Nouvelle position')
@section('page_title', 'Nouvelle position de grille')
@section('page_icon', 'fa-th')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('classification.positions.index') }}">Positions</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('classification.positions.store') }}">
            @csrf
            @include('classification.positions._form')

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="{{ route('classification.positions.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
