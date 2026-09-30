@extends('layouts.app')

@section('title', 'Positions de grille')
@section('page_title', 'Positions de la grille salariale')
@section('page_icon', 'fa-th')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Classification</li>
    <li>Positions</li>
@endsection

@section('page_actions')
    @can('permission', 'classification.manage')
        <a href="{{ route('classification.positions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle position
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <select name="referentiel_id" class="form-select">
                    <option value="">Tous les référentiels</option>
                    @foreach($referentiels as $r)
                        <option value="{{ $r->id }}" @selected(request('referentiel_id') == $r->id)>{{ $r->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="categorie_id" class="form-select">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('categorie_id') == $c->id)>{{ $c->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
                    <th>Code</th>
                    <th>Référentiel</th>
                    <th>Catégorie – Classe – Échelon</th>
                    <th class="text-end">Salaire (FCFA)</th>
                    <th class="text-end">Minimum (FCFA)</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($positions as $p)
                    <tr>
                        <td><code>{{ $p->code }}</code></td>
                        <td>{{ $p->referentiel?->nom }}</td>
                        <td>{{ $p->categorie?->libelle }} – {{ $p->classe?->libelle ?? '—' }} – {{ $p->echelon?->libelle ?? '—' }}</td>
                        <td class="text-end">{{ $p->montant_salaire !== null ? number_format($p->montant_salaire, 0, ',', ' ') : '—' }}</td>
                        <td class="text-end">{{ $p->salaire_minimum !== null ? number_format($p->salaire_minimum, 0, ',', ' ') : '—' }}</td>
                        <td><span class="badge bg-{{ $p->actif ? 'success' : 'secondary' }}">{{ $p->statut }}</span></td>
                        <td class="text-end">
                            @can('permission', 'classification.manage')
                                <a href="{{ route('classification.positions.edit', $p) }}" class="btn btn-sm btn-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune position.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $positions->links() }}</div>
</div>
@endsection
