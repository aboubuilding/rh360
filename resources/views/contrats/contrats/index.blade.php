@extends('layouts.app')

@section('title', 'Contrats & avenants')
@section('page_title', 'Registre des contrats & avenants')
@section('page_icon', 'fa-file-contract')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Dossiers RH</li>
    <li>Contrats</li>
@endsection

@section('page_actions')
    @can('permission', 'contrats.validate')
        <a href="{{ route('contrats.regles.index') }}" class="btn btn-secondary">
            <i class="fas fa-cog"></i> Règles
        </a>
        <a href="{{ route('contrats.parametres.edit') }}" class="btn btn-secondary">
            <i class="fas fa-sliders-h"></i> Paramètres
        </a>
    @endcan
    @can('permission', 'contrats.manage')
        <a href="{{ route('contrats.contrats.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau contrat
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="form-control" placeholder="Référence, salarié, matricule...">
            </div>
            <div class="col-md-2">
                <select name="type_contrat" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $val => $lib)
                        <option value="{{ $val }}" @selected(request('type_contrat') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="du" value="{{ request('du') }}" class="form-control" placeholder="Du">
            </div>
            <div class="col-md-2">
                <input type="date" name="au" value="{{ request('au') }}" class="form-control" placeholder="Au">
            </div>
            <div class="col-md-1">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i></button>
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
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Effet</th>
                    <th>Fin</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contrats as $c)
                    <tr>
                        <td>
                            <code>{{ $c->reference }}</code>
                            @if($c->estAvenant())
                                <span class="badge bg-info ms-1">Avenant</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $c->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $c->salarie?->matricule ?? '' }}</small>
                        </td>
                        <td>{{ $c->type_contrat }}</td>
                        <td>{{ $c->date_debut?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $c->date_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $c->statut->couleur() }}">
                                {{ $c->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('contrats.contrats.show', $c) }}">
                                            <i class="fas fa-eye"></i> Fiche
                                        </a>
                                    </li>
                                    @can('permission', 'contrats.manage')
                                        @if($c->estModifiable())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('contrats.contrats.edit', $c) }}">
                                                    <i class="fas fa-edit"></i> Modifier
                                                </a>
                                            </li>
                                        @endif
                                    @endcan
                                    @can('permission', 'contrats.validate')
                                        @if($c->statut === \App\Domain\Contrats\Enums\StatutContrat::BROUILLON)
                                            <li>
                                                <form method="POST" action="{{ route('contrats.contrats.soumettre', $c) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-paper-plane"></i> Soumettre
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    @endcan
                                    @can('permission', 'contrats.manage')
                                        @if($c->estSigne())
                                            <li>
                                                <a class="dropdown-item" href="{{ route('contrats.contrats.create-avenant', $c) }}">
                                                    <i class="fas fa-plus-square"></i> Créer un avenant
                                                </a>
                                            </li>
                                        @endif
                                    @endcan
                                    @can('permission', 'contrats.manage')
                                        @if($c->statut === \App\Domain\Contrats\Enums\StatutContrat::BROUILLON)
                                            <li>
                                                <form method="POST" action="{{ route('contrats.contrats.destroy', $c) }}"
                                                      class="form-confirm-delete"
                                                      data-confirm-title="Supprimer ce contrat ?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun contrat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $contrats->links() }}</div>
</div>
@endsection