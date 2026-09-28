@extends('layouts.app')

@section('title', 'Besoin ' . $besoin->reference)
@section('page_title', $besoin->intitule_poste)
@section('page_icon', 'fa-user-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('recrutement.besoins.index') }}">Besoins</a></li>
    <li>{{ $besoin->reference }}</li>
@endsection

@section('page_actions')
    @if($besoin->statut?->value === 'a_valider')
        @can('permission', 'recrutement.manage')
            <form method="POST" action="{{ route('recrutement.besoins.valider', $besoin) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Valider
                </button>
            </form>
        @endcan
    @endif
    @if($besoin->statut?->value === 'valide')
        @can('permission', 'recrutement.manage')
            <form method="POST" action="{{ route('recrutement.besoins.ouvrir', $besoin) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play"></i> Ouvrir le recrutement
                </button>
            </form>
        @endcan
    @endif
    @if(in_array($besoin->statut?->value, ['valide', 'en_cours']))
        @can('permission', 'recrutement.manage')
            <a href="{{ route('recrutement.candidats.create', ['besoin_id' => $besoin->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau candidat
            </a>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Statut</div>
                <span class="badge bg-{{ $besoin->statut?->couleur() }} fs-6">
                    {{ $besoin->statut?->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Postes</div>
                <div class="fw-bold fs-4">{{ $besoin->nombre_postes }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Candidats</div>
                <div class="fw-bold fs-4">{{ $besoin->candidats->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Retenus</div>
                <div class="fw-bold fs-4 text-success">{{ $besoin->nombreCandidatsRecrutes() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Référence</dt>
            <dd class="col-sm-9"><code>{{ $besoin->reference }}</code></dd>

            <dt class="col-sm-3">Département</dt>
            <dd class="col-sm-9">{{ $besoin->departement ?? '—' }}</dd>

            <dt class="col-sm-3">Type de contrat</dt>
            <dd class="col-sm-9">{{ $besoin->type_contrat }}</dd>

            <dt class="col-sm-3">Date cible</dt>
            <dd class="col-sm-9">{{ $besoin->date_cible?->format('d/m/Y') ?? '—' }}</dd>

            @if($besoin->motif)
                <dt class="col-sm-3">Motif</dt>
                <dd class="col-sm-9">{{ $besoin->motif }}</dd>
            @endif
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Candidats associés</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Étape</th>
                    <th>Décision</th>
                    <th>Score</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($besoin->candidats as $c)
                    <tr>
                        <td><strong>{{ $c->nom_complet }}</strong></td>
                        <td>{{ $c->email ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $c->etape?->couleur() }}">
                                {{ $c->etape?->libelle() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $c->decision?->couleur() }}">
                                {{ $c->decision?->libelle() }}
                            </span>
                        </td>
                        <td>{{ $c->score ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('recrutement.candidats.show', $c) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucun candidat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection