@extends('layouts.app')

@section('title', 'Importer des salariés')
@section('page_title', 'Importer des salariés depuis Excel')
@section('page_icon', 'fa-file-import')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li>Importer</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-3">1. Téléchargez le modèle</h5>
        <p class="text-muted">
            Le modèle définit les colonnes attendues : <code>nom</code>, <code>prenoms</code>,
            <code>sexe</code>, <code>date_naissance</code>, <code>telephone_principal</code>,
            <code>email_personnel</code>, <code>date_embauche</code>, <code>type_contrat</code>,
            <code>numero_cnss</code>.
        </p>
        <a href="{{ route('personnel.salaries.import.modele') }}" class="btn btn-outline-primary">
            <i class="fas fa-download"></i> Télécharger le modèle
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">2. Téléversez votre fichier</h5>

        <form method="POST" action="{{ route('personnel.salaries.import.apercu') }}"
              enctype="multipart/form-data">
            @csrf
            <x-field name="fichier" label="Fichier Excel (.xlsx, .xls, .csv)" type="file" required
                     help="10 Mo maximum." />
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Analyser le fichier
            </button>
            <a href="{{ route('personnel.salaries.index') }}" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
@endsection