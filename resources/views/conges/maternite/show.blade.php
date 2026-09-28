@extends('layouts.app')

@section('title', 'Dossier maternité')
@section('page_title', 'Dossier de maternité')
@section('page_icon', 'fa-baby')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.maternite.index') }}">Maternité</a></li>
    <li>Détail</li>
@endsection

@section('page_actions')
    @can('permission', 'conges.manage')
        <a href="{{ route('conges.maternite.edit', $dossier) }}" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Modifier
        </a>
    @endcan
    @if($dossier->chemin_certificat_medical)
        <a href="{{ route('conges.maternite.certificat', $dossier) }}" target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-medical"></i> Certificat médical
        </a>
    @endif
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-lock"></i> Données médicales confidentielles.
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Statut</div>
                <span class="badge bg-{{ $dossier->statut->couleur() }} fs-6">
                    {{ $dossier->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Salariée</div>
                <a href="{{ route('personnel.salaries.show', $dossier->salarie) }}" class="fw-bold">
                    {{ $dossier->salarie?->nom_complet }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Accouchement prévu</div>
                <div class="fw-bold">{{ $dossier->date_prevue_accouchement?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Début du congé</div>
                <div class="fw-bold">{{ $dossier->date_debut_conge?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Suivi médical</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Date de déclaration</dt>
                    <dd class="col-sm-6">{{ $dossier->date_declaration?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Consultation 1</dt>
                    <dd class="col-sm-6">{{ $dossier->date_consultation_1?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Consultation 2</dt>
                    <dd class="col-sm-6">{{ $dossier->date_consultation_2?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Consultation 3</dt>
                    <dd class="col-sm-6">{{ $dossier->date_consultation_3?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Risque au poste</dt>
                    <dd class="col-sm-6">
                        @if($dossier->risque_poste_identifie)
                            <span class="badge bg-warning text-dark">Identifié</span>
                        @else
                            Non
                        @endif
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Congé</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Début du congé</dt>
                    <dd class="col-sm-6">{{ $dossier->date_debut_conge?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Reprise effective</dt>
                    <dd class="col-sm-6">{{ $dossier->date_reprise_effective?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Référence acte</dt>
                    <dd class="col-sm-6">{{ $dossier->reference_acte ?? '—' }}</dd>

                    <dt class="col-sm-6">Date acte</dt>
                    <dd class="col-sm-6">{{ $dossier->date_acte?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">Créé par</dt>
                    <dd class="col-sm-6">{{ $dossier->creePar?->nom_complet ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($dossier->amenagement_temporaire)
            <hr>
            <h6 class="text-muted">Aménagement temporaire</h6>
            <p>{{ $dossier->amenagement_temporaire }}</p>
        @endif

        @if($dossier->observations)
            <h6 class="text-muted">Observations</h6>
            <p>{{ $dossier->observations }}</p>
        @endif
    </div>
</div>
@endsection