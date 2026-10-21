@extends('layouts.app')

@section('title', 'Paramètres des demandes')
@section('page_title', 'Paramètres des demandes et autorisations')
@section('page_icon', 'fa-sliders-h')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.demandes.index') }}">Congés & Absences</a></li>
    <li>Paramètres</li>
@endsection

@section('page_actions')
    <form method="POST" action="{{ route('conges.parametres.reinitialiser') }}"
          onsubmit="return confirm('Rétablir toutes les valeurs par défaut ?');">
        @csrf
        <button class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Rétablir les valeurs par défaut</button>
    </form>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Chaque paramètre affiche sa valeur par défaut. Les délais, justificatifs, plafonds et anciennetés propres
    à chaque type de demande, ainsi que les durées des permissions par événement, se règlent dans
    <a href="{{ route('conges.types.index') }}">Congés › Types de congés</a>. Les jours fériés se gèrent dans
    <a href="{{ route('conges.jours-feries.index') }}">Congés › Jours fériés</a>.
</div>

@include('partials._formulaire-parametres', ['routeEnregistrer' => route('conges.parametres.update')])
@endsection
