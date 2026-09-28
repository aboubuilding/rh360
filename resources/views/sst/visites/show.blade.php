@extends('layouts.app')

@section('title', 'Visite médicale')
@section('page_title', 'Visite médicale — ' . $visite->salarie?->nom_complet)
@section('page_icon', 'fa-stethoscope')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.visites.index') }}">Visites médicales</a></li>
    <li>Détail</li>
@endsection

@section('page_actions')
    <a href="{{ route('sst.visites.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
@endsection

@section('contenu')
<div class="alert alert-warning small">
    <i class="fas fa-lock"></i>
    Consultation tracée. Les données médicales sont strictement confidentielles.
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $visite->statut->couleur() }} fs-6">
                    {{ $visite->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Aptitude</div>
                @if($visite->aptitude)
                    <span class="badge bg-{{ $visite->aptitude->couleur() }} fs-6">
                        {{ $visite->aptitude->libelle() }}
                    </span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Date prévue</div>
                <div class="fw-bold">{{ $visite->date_prevue?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Date réalisée</div>
                <div class="fw-bold">{{ $visite->date_realisation?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Salarié</dt>
                    <dd class="col-sm-7">{{ $visite->salarie?->nom_complet }}</dd>

                    <dt class="col-sm-5">Matricule</dt>
                    <dd class="col-sm-7"><code>{{ $visite->salarie?->matricule }}</code></dd>

                    <dt class="col-sm-5">Type de visite</dt>
                    <dd class="col-sm-7">{{ $visite->type_visite?->libelle() }}</dd>

                    <dt class="col-sm-5">Prestataire</dt>
                    <dd class="col-sm-7">{{ $visite->prestataire ?? '—' }}</dd>

                    <dt class="col-sm-5">Référence avis</dt>
                    <dd class="col-sm-7">{{ $visite->reference_avis ?? '—' }}</dd>

                    <dt class="col-sm-5">Prochaine échéance</dt>
                    <dd class="col-sm-7">
                        <strong>{{ $visite->date_prochaine_echeance?->format('d/m/Y') ?? '—' }}</strong>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Créée par</dt>
                    <dd class="col-sm-7">{{ $visite->creePar?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-5">Créée le</dt>
                    <dd class="col-sm-7">{{ $visite->created_at->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5">Modifiée par</dt>
                    <dd class="col-sm-7">{{ $visite->modifiePar?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-5">Révision</dt>
                    <dd class="col-sm-7">{{ $visite->revision }}</dd>

                    @if($visite->visiteOrigine)
                        <dt class="col-sm-5">Visite d'origine</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('sst.visites.show', $visite->visiteOrigine) }}">
                                {{ $visite->visiteOrigine->date_prevue?->format('d/m/Y') }}
                            </a>
                        </dd>
                    @endif

                    @if($visite->motif_annulation)
                        <dt class="col-sm-5 text-danger">Motif annulation</dt>
                        <dd class="col-sm-7 text-danger">{{ $visite->motif_annulation }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($visite->restrictions)
            <hr>
            <h6 class="text-muted"><i class="fas fa-exclamation-triangle"></i> Restrictions / aménagements</h6>
            <p class="mb-0">{{ $visite->restrictions }}</p>
        @endif
    </div>
</div>
@endsection