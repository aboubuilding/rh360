@extends('layouts.app')

@section('title', 'Règles d\'évolution')
@section('page_title', 'Règles d\'évolution')
@section('page_icon', 'fa-level-up-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Classification</li>
    <li>Règles d'évolution</li>
@endsection

@section('page_actions')
    @can('permission', 'classification.manage')
        <a href="{{ route('classification.regles-evolution.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle règle
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
                <select name="type_evolution" class="form-select">
                    <option value="">Tous les types</option>
                    @foreach(['echelon' => 'Échelon', 'classe' => 'Classe', 'categorie' => 'Catégorie'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('type_evolution') === $v)>{{ $l }}</option>
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
                    <th>Type</th>
                    <th>Délai min / max (mois)</th>
                    <th>Anticipation</th>
                    <th>Validation requise</th>
                    <th>Priorité</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($regles as $r)
                    <tr>
                        <td><code>{{ $r->code }}</code></td>
                        <td>{{ $r->referentiel?->nom }}</td>
                        <td>{{ $r->type_evolution }}</td>
                        <td>{{ $r->mois_min ?? '—' }} / {{ $r->mois_max ?? '—' }}</td>
                        <td>{{ $r->anticipation_autorisee ? 'Oui' : 'Non' }}</td>
                        <td>{{ $r->validation_requise ? 'Oui' : 'Non' }}</td>
                        <td>{{ $r->priorite }}</td>
                        <td class="text-end">
                            @can('permission', 'classification.manage')
                                <a href="{{ route('classification.regles-evolution.edit', $r) }}" class="btn btn-sm btn-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune règle d'évolution.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $regles->links() }}</div>
</div>
@endsection
