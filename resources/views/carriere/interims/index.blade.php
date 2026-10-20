@extends('layouts.app')

@section('title', 'Intérims internes')
@section('page_title', 'Intérims internes')
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Intérims</li>
@endsection

@section('page_actions')
    @can('permission', 'carriere.manage')
        <a href="{{ route('carriere.interims.parametres') }}" class="btn btn-outline-secondary">
            <i class="fas fa-sliders-h"></i> Paramètres
        </a>
        <a href="{{ route('carriere.interims.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvel intérim
        </a>
    @endcan
@endsection

@section('contenu')
@if($alertes->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-bell"></i> Alertes en cours ({{ $alertes->count() }})</div>
        <ul class="list-group list-group-flush">
            @foreach($alertes as $a)
                <li class="list-group-item">
                    <span class="badge bg-{{ $a->niveau }} me-1">{{ $a->date_echeance?->format('d/m/Y') }}</span>
                    @if($a->objet_type === (new \App\Domain\Interim\Models\InterimInterne)->getMorphClass())
                        <a href="{{ route('carriere.interims.show', $a->objet_id) }}"><strong>{{ $a->intitule }}</strong></a>
                    @else
                        <strong>{{ $a->intitule }}</strong>
                    @endif
                    <br><small class="text-muted">{{ $a->message }}</small>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, prénoms, matricule...">
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
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
                    <th>Poste occupé</th>
                    <th>Motif</th>
                    <th>Début</th>
                    <th>Fin prévue</th>
                    <th>Date limite</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interims as $i)
                    <tr>
                        <td>
                            <strong>{{ $i->salarie?->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $i->salarie?->matricule }}</small>
                        </td>
                        <td>
                            {{ $i->posteOccupe?->intitule ?? '—' }}
                            <br><small class="text-muted">{{ $i->categorieOccupee?->libelle }}</small>
                        </td>
                        <td>{{ $i->motif->libelle() }}</td>
                        <td>{{ $i->date_debut->format('d/m/Y') }}</td>
                        <td>{{ $i->date_fin_reelle?->format('d/m/Y') ?? $i->date_fin_prevue->format('d/m/Y') }}</td>
                        <td>{{ $i->date_limite->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $i->statut->couleur() }}">{{ $i->statut->libelle() }}</span>
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

@if($nonRepris->isNotEmpty())
    <div class="card mt-3 border-warning">
        <div class="card-header bg-warning-subtle">
            <i class="fas fa-exclamation-triangle"></i>
            Intérims saisis avant la mise en conformité, non encore repris ({{ $nonRepris->count() }})
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Salarié</th>
                        <th>Poste</th>
                        <th>Début</th>
                        <th>Fin prévue</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($nonRepris as $m)
                        <tr>
                            <td><a href="{{ route('carriere.mouvements.show', $m) }}"><code>{{ $m->numero_mouvement }}</code></a></td>
                            <td>{{ $m->salarie?->nom_complet }}</td>
                            <td>{{ $m->posteCible?->intitule ?? '—' }}</td>
                            <td>{{ $m->date_effet?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $m->date_fin_prevue?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="badge bg-{{ $m->statut->couleur() }}">{{ $m->statut->libelle() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
