@extends('layouts.app')

@section('title', 'Besoins de recrutement')
@section('page_title', 'Besoins de recrutement')
@section('page_icon', 'fa-user-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Recrutement</li>
    <li>Besoins</li>
@endsection

@section('page_actions')
    @can('permission', 'recrutement.manage')
        <a href="{{ route('recrutement.besoins.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau besoin
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Référence, poste, département...">
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
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
                    <th>Référence</th>
                    <th>Poste</th>
                    <th>Département</th>
                    <th>Nb postes</th>
                    <th>Type contrat</th>
                    <th>Date cible</th>
                    <th>Candidats</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($besoins as $b)
                    <tr>
                        <td><code>{{ $b->reference }}</code></td>
                        <td><strong>{{ $b->intitule_poste }}</strong></td>
                        <td>{{ $b->departement ?? '—' }}</td>
                        <td>{{ $b->nombre_postes }}</td>
                        <td>{{ $b->type_contrat }}</td>
                        <td>{{ $b->date_cible?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $b->candidats->count() }}</span>
                            <small class="text-muted">/{{ $b->nombreCandidatsRecrutes() }} retenus</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $b->statut?->couleur() }}">
                                {{ $b->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('recrutement.besoins.show', $b) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucun besoin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $besoins->links() }}</div>
</div>
@endsection