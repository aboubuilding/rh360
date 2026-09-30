@extends('layouts.app')

@php
    $titresEtapes = [
        1 => 'Identité',
        2 => 'Coordonnées & domicile',
        3 => 'Famille & urgence',
        4 => 'Social & bancaire',
        5 => 'Situation professionnelle',
    ];
@endphp

@section('title', 'Nouveau salarié')
@section('page_title', 'Nouveau salarié — étape ' . $numero . ' / ' . $total)
@section('page_icon', 'fa-user-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body py-2">
        <ol class="nav nav-pills nav-justified small mb-0">
            @foreach($titresEtapes as $n => $titre)
                <li class="nav-item">
                    <a class="nav-link {{ $n === $numero ? 'active' : '' }}"
                       href="{{ route('personnel.salaries.wizard.etape', $n) }}">
                        {{ $n }}. {{ $titre }}
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>{{ $numero }}. {{ $titresEtapes[$numero] }}</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('personnel.salaries.wizard.etape.store', $numero) }}"
              enctype="multipart/form-data">
            @csrf
            @include('personnel.salaries._wizard_etapes._etape' . $numero)

            <div class="d-flex justify-content-between mt-4">
                <div>
                    @if($numero > 1)
                        <a href="{{ route('personnel.salaries.wizard.etape', $numero - 1) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Précédent
                        </a>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">
                    {{ $numero < $total ? 'Enregistrer et continuer' : 'Enregistrer et vérifier' }}
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>

        <hr>
        <form method="POST" action="{{ route('personnel.salaries.wizard.abandonner') }}" class="text-end"
              onsubmit="return confirm('Abandonner ce brouillon de création ?')">
            @csrf
            <button type="submit" class="btn btn-link text-danger">
                <i class="fas fa-trash"></i> Abandonner le brouillon
            </button>
        </form>
    </div>
</div>
@endsection
