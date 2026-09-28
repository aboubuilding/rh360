@extends('layouts.app')

@section('title', 'Entretiens d\'évaluation')
@section('page_title', 'Entretiens d\'évaluation')
@section('page_icon', 'fa-comments')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Performance</li>
    <li>Entretiens</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="campagne_id" class="form-select">
                    <option value="">Toutes les campagnes</option>
                    @foreach($campagnes as $c)
                        <option value="{{ $c->id }}" @selected(request('campagne_id') == $c->id)>{{ $c->intitule }}</option>
                    @endforeach
                </select>
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
                    <th>Salarié</th>
                    <th>Campagne</th>
                    <th>Statut</th>
                    <th class="text-end">Note finale</th>
                    <th>Date entretien</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entretiens as $e)
                    <tr>
                        <td><strong>{{ $e->salarie?->nom_complet }}</strong></td>
                        <td>{{ $e->campagne?->intitule }}</td>
                        <td>
                            <span class="badge bg-{{ $e->statut?->couleur() }}">
                                {{ $e->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end fw-bold">{{ $e->note_finale ?? '—' }}</td>
                        <td>{{ $e->date_entretien?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('performance.entretiens.show', $e) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun entretien.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $entretiens->links() }}</div>
</div>
@endsection