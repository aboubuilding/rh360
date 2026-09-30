@extends('layouts.app')

@section('title', 'Nouvelle règle d\'évolution')
@section('page_title', 'Nouvelle règle d\'évolution')
@section('page_icon', 'fa-level-up-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('classification.regles-evolution.index') }}">Règles d'évolution</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('classification.regles-evolution.store') }}">
            @csrf
            @include('classification.regles-evolution._form')

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="{{ route('classification.regles-evolution.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
