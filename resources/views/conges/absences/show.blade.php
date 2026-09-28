@extends('layouts.app')

@section('title', 'Absence')
@section('page_title', 'Détail de l\'absence')
@section('page_icon', 'fa-user-slash')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.absences.index') }}">Absences</a></li>
    <li>Détail</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Salarié</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('personnel.salaries.show', $absence->salarie) }}">
                            {{ $absence->salarie?->nom_complet }}
                        </a>
                    </dd>

                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7">{{ $absence->typeConge?->nom }}</dd>

                    <dt class="col-sm-5">Début</dt>
                    <dd class="col-sm-7">{{ $absence->debut_le?->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5">Fin</dt>
                    <dd class="col-sm-7">
                        {{ $absence->fin_le?->format('d/m/Y H:i') ?? 'Absence ouverte' }}
                    </dd>

                    <dt class="col-sm-5">Durée</dt>
                    <dd class="col-sm-7">{{ number_format($absence->duree_heures ?? $absence->dureeJours(), 2) }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Qualification</dt>
                    <dd class="col-sm-7">
                        @if($absence->qualification)
                            <span class="badge bg-{{ $absence->qualification->couleur() }}">
                                {{ $absence->qualification->libelle() }}
                            </span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">État paie</dt>
                    <dd class="col-sm-7">
                        @if($absence->statut_transmission_paie)
                            <span class="badge bg-{{ $absence->statut_transmission_paie->couleur() }}">
                                {{ $absence->statut_transmission_paie->libelle() }}
                            </span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Période paie</dt>
                    <dd class="col-sm-7">{{ $absence->periode_paie ?? '—' }}</dd>

                    <dt class="col-sm-5">Transmis le</dt>
                    <dd class="col-sm-7">{{ $absence->transmis_paie_le?->format('d/m/Y H:i') ?? '—' }}</dd>

                    <dt class="col-sm-5">Transmis par</dt>
                    <dd class="col-sm-7">{{ $absence->transmisPaiePar?->nom_complet ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($absence->motif)
            <hr>
            <h6 class="text-muted">Motif</h6>
            <p>{{ $absence->motif }}</p>
        @endif

        @if($absence->justification)
            <h6 class="text-muted">Justification</h6>
            <p>{{ $absence->justification }}</p>
        @endif

        @if($absence->decision_regularisation)
            <h6 class="text-muted">Décision de régularisation</h6>
            <p class="fst-italic">{{ $absence->decision_regularisation }}</p>
        @endif

        <a href="{{ route('conges.absences.index') }}" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>
@endsection