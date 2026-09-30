@extends('layouts.app')

@section('title', 'Récapitulatif')
@section('page_title', 'Récapitulatif avant création')
@section('page_icon', 'fa-clipboard-check')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('personnel.salaries.index') }}">Salariés</a></li>
    <li>Nouveau</li>
    <li>Récapitulatif</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Vérifiez les informations ci-dessous avant de valider la création définitive.
        </div>

        @php
            $sections = [
                'Identité' => [
                    'Nom' => $donnees['nom'] ?? '—',
                    'Prénoms' => $donnees['prenoms'] ?? '—',
                    'Sexe' => $donnees['sexe'] ?? '—',
                    'Date de naissance' => $donnees['date_naissance'] ?? '—',
                    'Lieu de naissance' => $donnees['lieu_naissance'] ?? '—',
                    'Nationalité' => $donnees['nationalite'] ?? '—',
                ],
                'Coordonnées' => [
                    'Téléphone principal' => $donnees['telephone_principal'] ?? '—',
                    'Email personnel' => $donnees['email_personnel'] ?? '—',
                    'Adresse' => $donnees['adresse'] ?? '—',
                    'Ville' => $donnees['ville'] ?? '—',
                    'Pays' => $donnees['pays_residence'] ?? '—',
                ],
                'Famille' => [
                    'Situation matrimoniale' => $donnees['situation_matrimoniale'] ?? '—',
                    'Contact urgence' => $donnees['contact_urgence_nom'] ?? '—',
                    'Téléphone urgence' => $donnees['contact_urgence_telephone'] ?? '—',
                ],
                'Social & bancaire' => [
                    'N° CNSS' => $donnees['numero_cnss'] ?? '—',
                    'N° AMU' => $donnees['numero_amu'] ?? '—',
                    'Banque' => $donnees['banque'] ?? '—',
                    'Mode de paiement' => $donnees['mode_paiement'] ?? '—',
                ],
                'Situation professionnelle' => [
                    'Date d\'embauche' => $donnees['date_embauche'] ?? '—',
                    'Structure' => $structure?->nom ?? '—',
                    'Poste' => $poste?->intitule ?? '—',
                    'Classification' => $position?->code ?? '—',
                    'Effet de l\'échelon' => $donnees['date_effet_echelon'] ?? '—',
                    'Type de contrat' => $donnees['type_contrat'] ?? '—',
                    'Référence du contrat' => $donnees['reference_contrat'] ?? '—',
                    'Lieu d\'affectation' => $donnees['lieu_affectation'] ?? '—',
                ],
            ];
        @endphp

        <div class="row">
            @foreach($sections as $titre => $champs)
                <div class="col-md-6 mb-3">
                    <h6 class="text-primary border-bottom pb-1">{{ $titre }}</h6>
                    <dl class="row mb-0">
                        @foreach($champs as $label => $valeur)
                            <dt class="col-sm-6">{{ $label }}</dt>
                            <dd class="col-sm-6">{{ $valeur ?: '—' }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>

        <hr>

        {{-- Deux formulaires distincts (jamais imbriqués) : « Abandonner » ne doit pas valider la création --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('personnel.salaries.wizard.etape', 5) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Modifier
            </a>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('personnel.salaries.wizard.abandonner') }}"
                      onsubmit="return confirm('Abandonner la création ?')">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger">Abandonner</button>
                </form>
                <form method="POST" action="{{ route('personnel.salaries.wizard.valider') }}" id="form-validation">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Valider la création
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection