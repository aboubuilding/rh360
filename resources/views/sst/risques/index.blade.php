@extends('layouts.app')

@section('title', 'Risques professionnels')
@section('page_title', 'Registre des risques professionnels')
@section('page_icon', 'fa-exclamation-triangle')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>Risques & prévention</li>
@endsection

@section('page_actions')
    @can('permission', 'risks.manage')
        <a href="{{ route('sst.risques.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau risque
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, site, activité...">
            </div>
            <div class="col-md-3">
                <select name="famille" class="form-select">
                    <option value="">Toutes familles</option>
                    @foreach($familles as $val => $lib)
                        <option value="{{ $val }}" @selected(request('famille') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    <option value="active" @selected(request('statut') === 'active')>Actifs</option>
                    <option value="archived" @selected(request('statut') === 'archived')>Archivés</option>
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
                    <th>Intitulé</th>
                    <th>Famille</th>
                    <th>Site</th>
                    <th>Poste</th>
                    <th>Dernière évaluation</th>
                    <th>Revue</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($risques as $r)
                    @php
                        $evaluation = $r->derniereEvaluation();
                        $niveau = $r->niveauDerniereEvaluation();
                    @endphp
                    <tr>
                        <td><strong>{{ $r->intitule }}</strong></td>
                        <td>{{ $r->famille?->libelle() }}</td>
                        <td>{{ $r->site }}</td>
                        <td>{{ $r->poste?->intitule ?? '—' }}</td>
                        <td>
                            @if($evaluation)
                                <span class="badge bg-{{ $niveau?->couleur() ?? 'secondary' }}">
                                    {{ $niveau?->libelle() ?? '—' }} ({{ $evaluation->score }})
                                </span>
                            @else
                                <span class="text-muted small">Non évalué</span>
                            @endif
                        </td>
                        <td>
                            {{ $r->date_echeance_revue?->format('d/m/Y') ?? '—' }}
                            @if($r->date_echeance_revue && $r->date_echeance_revue->isPast())
                                <span class="badge bg-danger">En retard</span>
                            @endif
                        </td>
                        <td>
                            @if($r->statut === 'active')
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Archivé</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sst.risques.show', $r) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun risque.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $risques->links() }}</div>
</div>
@endsection