@extends('layouts.app')

@section('title', 'Candidats')
@section('page_title', 'Candidats au recrutement')
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Recrutement</li>
    <li>Candidats</li>
@endsection

@section('page_actions')
    @can('permission', 'recrutement.manage')
        <a href="{{ route('recrutement.candidats.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau candidat
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, email, téléphone...">
            </div>
            <div class="col-md-2">
                <select name="besoin_id" class="form-select">
                    <option value="">Tous les besoins</option>
                    @foreach($besoins as $b)
                        <option value="{{ $b->id }}" @selected(request('besoin_id') == $b->id)>{{ $b->reference }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="etape" class="form-select">
                    <option value="">Toutes étapes</option>
                    @foreach($etapes as $val => $lib)
                        <option value="{{ $val }}" @selected(request('etape') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="decision" class="form-select">
                    <option value="">Toutes décisions</option>
                    @foreach($decisions as $val => $lib)
                        <option value="{{ $val }}" @selected(request('decision') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Besoin</th>
                    <th>Source</th>
                    <th>Étape</th>
                    <th>Décision</th>
                    <th>Score</th>
                    <th>Date entretien</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidats as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $c->email ?? $c->telephone }}</small>
                        </td>
                        <td>{{ $c->besoin?->reference ?? '—' }}</td>
                        <td>{{ $c->source?->libelle() }}</td>
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
                        <td>{{ $c->date_entretien?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('recrutement.candidats.show', $c) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun candidat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $candidats->links() }}</div>
</div>
@endsection