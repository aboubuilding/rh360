@extends('layouts.app')

@section('title', 'Situations de carrière')
@section('page_title', 'Situations de carrière')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Situations</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, matricule...">
            </div>
            <div class="col-md-3">
                <select name="fiabilite" class="form-select">
                    <option value="">Toutes fiabilités</option>
                    @foreach($fiabilites as $val => $lib)
                        <option value="{{ $val }}" @selected(request('fiabilite') === $val)>{{ $lib }}</option>
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
                    <th>Position</th>
                    <th>Effet échelon</th>
                    <th>Réf. avancement</th>
                    <th>Fiabilité</th>
                    <th>Historique</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($situations as $s)
                    <tr>
                        <td>
                            <strong>{{ $s->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $s->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $s->positionClassification?->libelleComplet() ?? '—' }}</td>
                        <td>{{ $s->date_effet_echelon?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $s->date_reference_avancement?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($s->statut_fiabilite)
                                <span class="badge bg-{{ $s->statut_fiabilite->couleur() }}">
                                    {{ $s->statut_fiabilite->libelle() }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($s->statut_historique)
                                <span class="badge bg-{{ $s->statut_historique->couleur() }}">
                                    {{ $s->statut_historique->libelle() }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('carriere.situations.show', $s->salarie) }}"
                               class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune situation enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $situations->links() }}</div>
</div>
@endsection