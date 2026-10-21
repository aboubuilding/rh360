@extends('layouts.app')

@section('title', 'Paramètres des avancements')
@section('page_title', 'Paramètres des avancements et de l\'ancienneté')
@section('page_icon', 'fa-sliders-h')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.avancements.index') }}">Avancements</a></li>
    <li>Paramètres</li>
@endsection

@section('page_actions')
    <form method="POST" action="{{ route('carriere.avancements.parametres.reinitialiser') }}"
          onsubmit="return confirm('Rétablir toutes les valeurs par défaut ?');">
        @csrf
        <button class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Rétablir les valeurs par défaut</button>
    </form>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Chaque paramètre affiche sa valeur par défaut. Le taux et la base de la prime d'ancienneté se règlent dans
    <a href="{{ route('paie.parametres.index') }}">Paie › Paramètres</a> (règle d'ancienneté).
    Les absences déduites de l'ancienneté se règlent par type de congé (impact sur l'ancienneté) dans
    <a href="{{ route('conges.types.index') }}">Congés › Types</a>.
</div>

@include('partials._formulaire-parametres', ['routeEnregistrer' => route('carriere.avancements.parametres.update')])
@endsection
