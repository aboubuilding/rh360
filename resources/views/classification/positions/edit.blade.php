@extends('layouts.app')

@section('title', 'Modifier la position')
@section('page_title', 'Modifier la position ' . $position->code)
@section('page_icon', 'fa-th')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('classification.positions.index') }}">Positions</a></li>
    <li>Modifier</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('classification.positions.update', $position) }}">
            @csrf
            @method('PUT')
            @include('classification.positions._form', ['position' => $position])

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="{{ route('classification.positions.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
