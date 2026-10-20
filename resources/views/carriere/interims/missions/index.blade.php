@extends('layouts.app')

@section('title', 'Missions d\'intérim')
@section('page_title', 'Missions d\'intérim (agences)')
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Missions d'intérim</li>
@endsection

@section('page_actions')
    @can('create', \App\Domain\Interim\Models\MissionInterim::class)
        <a href="{{ route('carriere.interims.missions.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle mission</a>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Les intérimaires sont salariés de l'agence : ils ne font pas partie de l'effectif et n'ont ni paie ni cotisations
    dans l'application (Code du travail, art. 60 à 63).
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Réf.</th>
                    <th>Intérimaire</th>
                    <th>Agence</th>
                    <th>Poste</th>
                    <th>Période</th>
                    <th>SST</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($missions as $m)
                    <tr>
                        <td><code>{{ $m->reference }}</code></td>
                        <td>{{ $m->nom_interimaire }}</td>
                        <td>{{ $m->agence?->raison_sociale }}</td>
                        <td>{{ $m->poste?->intitule }}</td>
                        <td>{{ $m->date_debut->format('d/m/Y') }} → {{ ($m->date_fin_reelle ?? $m->date_fin)->format('d/m/Y') }}</td>
                        <td>
                            @if($m->checklistSstComplete())
                                <span class="badge bg-success">Validée</span>
                            @else
                                <span class="badge bg-warning text-dark">À valider</span>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $m->statut->couleur() }}">{{ $m->statut->libelle() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('carriere.interims.missions.show', $m) }}" class="btn btn-sm btn-action"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune mission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $missions->links() }}</div>
</div>
@endsection
