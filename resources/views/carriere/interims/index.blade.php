@extends('layouts.app')

@section('title', 'Intérims')
@section('page_title', 'Missions d\'intérim')
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Intérims</li>
@endsection

@section('page_actions')
    @can('permission', 'carriere.manage')
        <a href="{{ route('carriere.interims.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel intérim
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Numéro, salarié...">
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous</option>
                    <option value="en_cours" @selected(request('statut') === 'en_cours')>En cours</option>
                    <option value="clotures" @selected(request('statut') === 'clotures')>Clôturés</option>
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
                    <th>N°</th>
                    <th>Salarié</th>
                    <th>Poste temporaire</th>
                    <th>Début</th>
                    <th>Fin prévue</th>
                    <th>Fin réelle</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interims as $i)
                    <tr>
                        <td><code>{{ $i->numero_mouvement }}</code></td>
                        <td>
                            <strong>{{ $i->salarie?->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $i->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $i->posteCible?->intitule ?? '—' }}</td>
                        <td>{{ $i->date_effet?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $i->date_fin_prevue?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $i->date_fin_reelle?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $i->statut->couleur() }}">
                                {{ $i->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('carriere.interims.show', $i) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun intérim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $interims->links() }}</div>
</div>
@endsection