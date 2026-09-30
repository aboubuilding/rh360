@extends('layouts.app')

@section('title', 'Organigramme')
@section('page_title', 'Organigramme')
@section('page_icon', 'fa-project-diagram')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('organisation.structures.index') }}">Structures</a></li>
    <li>Organigramme</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        @if($racines->isEmpty())
            <p class="text-muted text-center py-4">Aucune structure racine. Créez d'abord des structures sans parent.</p>
        @else
            <div class="organigramme">
                @foreach($racines as $racine)
                    @include('organisation.structures._noeud', ['structure' => $racine])
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    .organigramme ul { list-style: none; padding-left: 20px; border-left: 2px solid #dee2e6; }
    .organigramme li { position: relative; padding: 8px 0 8px 20px; }
    .organigramme li::before {
        content: ''; position: absolute; left: 0; top: 18px;
        width: 16px; height: 2px; background: #dee2e6;
    }
    .organigramme .noeud {
        display: inline-block; padding: 6px 12px; background: #f8f9fa;
        border: 1px solid #dee2e6; border-radius: 4px;
    }
    .organigramme .noeud .code { color: #6c757d; font-size: 0.8rem; }
</style>
@endsection