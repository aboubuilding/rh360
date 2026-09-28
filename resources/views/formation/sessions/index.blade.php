@extends('layouts.app')

@section('title', 'Sessions de formation')
@section('page_title', 'Sessions de formation')
@section('page_icon', 'fa-chalkboard-teacher')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Formation</li>
    <li>Sessions</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <a href="{{ route('formation.sessions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle session
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, prestataire...">
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
                    <th>Intitulé</th>
                    <th>Prestataire</th>
                    <th>Période</th>
                    <th>Durée</th>
                    <th>Participants</th>
                    <th class="text-end">Coût</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                    <tr>
                        <td><strong>{{ $s->intitule }}</strong></td>
                        <td>{{ $s->prestataire ?? '—' }}</td>
                        <td>
                            {{ $s->date_debut?->format('d/m/Y') }}
                            <i class="fas fa-arrow-right mx-1"></i>
                            {{ $s->date_fin?->format('d/m/Y') }}
                        </td>
                        <td>{{ number_format($s->duree_heures, 1) }} h</td>
                        <td>{{ $s->participants()->count() }}</td>
                        <td class="text-end">{{ number_format($s->cout_reel, 0, ',', ' ') }}</td>
                        <td>
                            <span class="badge bg-{{ $s->statut?->couleur() }}">
                                {{ $s->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('formation.sessions.show', $s) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $sessions->links() }}</div>
</div>
@endsection