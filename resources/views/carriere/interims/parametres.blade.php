@extends('layouts.app')

@section('title', 'Paramètres des intérims')
@section('page_title', 'Paramètres des intérims')
@section('page_icon', 'fa-sliders-h')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>Paramètres</li>
@endsection

@section('page_actions')
    <form method="POST" action="{{ route('carriere.interims.parametres.reinitialiser') }}"
          onsubmit="return confirm('Rétablir toutes les valeurs par défaut ?');">
        @csrf
        <button class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Rétablir les valeurs par défaut</button>
    </form>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Chaque paramètre affiche sa valeur par défaut. Le barème des salaires minimums se saisit dans
    <a href="{{ route('classification.referentiels.index') }}">Classification › Référentiels</a> (positions et salaires minimums).
</div>

@include('partials._formulaire-parametres', ['routeEnregistrer' => route('carriere.interims.parametres.update')])
@endsection
