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

{{-- Absence constatée sans demande : régularisation par une demande (force majeure). --}}
<div class="card mt-3">
    <div class="card-header"><strong>Régularisation par une demande</strong></div>
    <div class="card-body">
        @if($demandeLiee)
            Cette absence est régularisée par la demande
            <a href="{{ route('conges.demandes.show', $demandeLiee) }}">{{ $demandeLiee->numero_demande }}</a>
            <span class="badge bg-{{ $demandeLiee->statut->couleur() }}">{{ $demandeLiee->statut->libelle() }}</span>.
        @elseif($absence->qualification?->value === 'en_attente')
            @can('permission', 'conges.manage')
                <p class="text-muted small">
                    Si l'absence relève d'une absence autorisée (décès, maladie…) prise sans demande préalable,
                    une demande est créée pour ses dates et soumise aussitôt. Le délai de remise du justificatif
                    court depuis l'événement ou le premier jour d'absence.
                </p>
                <form method="POST" action="{{ route('conges.absences.regulariser-par-demande', $absence) }}" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-6">
                        <x-field name="type_conge_id" label="Type de demande" type="select" required
                                 :options="[null => '—'] + $typesRegularisation->pluck('nom', 'id')->all()" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-field name="evenement_permission_id" label="Événement (permission exceptionnelle)" type="select"
                                 :options="[null => '—'] + $evenements->mapWithKeys(fn ($e) => [$e->id => $e->libelle])->all()" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-field name="date_evenement" label="Date de l'événement" type="date" />
                    </div>
                    <div class="col-12">
                        <x-field name="motif_force_majeure" label="Cas de force majeure" type="textarea" required />
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="fas fa-file-signature"></i> Créer la demande de régularisation</button>
                    </div>
                </form>
            @endcan
        @else
            <span class="text-muted">L'absence est déjà qualifiée.</span>
        @endif
    </div>
</div>
@endsection