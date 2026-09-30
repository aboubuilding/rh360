@extends('layouts.app')

@section('title', 'Salariés')
@section('page_title', 'Annuaire des salariés')
@section('page_icon', 'fa-users')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Dossiers RH</li>
    <li>Salariés</li>
@endsection

@section('page_actions')
    @can('permission', 'salaries.import')
        <a href="{{ route('personnel.salaries.import') }}" class="btn btn-secondary">
            <i class="fas fa-file-import"></i> Importer
        </a>
    @endcan
    @can('permission', 'salaries.manage')
        <a href="{{ route('personnel.salaries.wizard.demarrer') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau salarié
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2" role="search" aria-label="Filtrer les salariés">
            <div class="col-md-3">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" aria-label="Rechercher un salarié"
                       placeholder="Nom, matricule, n° enregistrement...">
            </div>
            <div class="col-md-3">
                <select name="structure_id" class="form-select" aria-label="Structure">
                    <option value="">Toutes les structures</option>
                    @foreach($structures as $s)
                        <option value="{{ $s->id }}" @selected(request('structure_id') == $s->id)>{{ $s->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="situation" class="form-select" aria-label="Situation">
                    <option value="">Tous</option>
                    <option value="actifs" @selected(request('situation') === 'actifs')>Actifs</option>
                    <option value="anciens" @selected(request('situation') === 'anciens')>Anciens</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="completude" class="form-select" aria-label="Complétude du dossier">
                    <option value="">Complétude</option>
                    <option value="complets" @selected(request('completude') === 'complets')>Complets</option>
                    <option value="incomplets" @selected(request('completude') === 'incomplets')>À compléter</option>
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
                    <th style="width:60px;">Photo</th>
                    <th>Matricule</th>
                    <th>Nom et prénoms</th>
                    <th>Poste / Structure</th>
                    <th>Embauche</th>
                    <th>Statut dossier</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $s)
                    @php $aff = $s->affectations->firstWhere('en_cours', true); @endphp
                    <tr>
                        <td>
                            @if($s->chemin_photo)
                                <img src="{{ route('personnel.salaries.photo', $s) }}"
                                     class="rounded-circle"
                                     style="width:40px;height:40px;object-fit:cover;" alt="">
                            @else
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                     style="width:40px;height:40px;font-size:.75rem;">
                                    {{ $s->initiales }}
                                </div>
                            @endif
                        </td>
                        <td><code>{{ $s->matricule }}</code></td>
                        <td>
                            <strong>{{ $s->nom_complet }}</strong>
                            @if($s->est_fusionne)
                                <span class="badge bg-info">Fusionné</span>
                            @endif
                        </td>
                        <td>
                            {{ $aff?->poste?->intitule ?? '—' }}<br>
                            <small class="text-muted">{{ $aff?->structure?->nom ?? '—' }}</small>
                        </td>
                        <td>{{ $s->date_embauche?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($s->statut_dossier === \App\Domain\Personnel\Enums\StatutDossier::COMPLET)
                                <span class="badge bg-success">Complet</span>
                            @else
                                <span class="badge bg-warning text-dark">À compléter</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('personnel.salaries.show', $s) }}">
                                            <i class="fas fa-eye"></i> Fiche
                                        </a>
                                    </li>
                                    @can('permission', 'salaries.manage')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('personnel.salaries.edit', $s) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </a>
                                        </li>
                                    @endcan
                                    @can('permission', 'salaries.fusion')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('personnel.salaries.fusion', $s) }}">
                                                <i class="fas fa-code-branch"></i> Fusionner
                                            </a>
                                        </li>
                                    @endcan
                                    @can('permission', 'salaries.manage')
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('personnel.salaries.destroy', $s) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer ce salarié ?"
                                                  data-confirm-text="Le dossier sera masqué mais conservé.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Aucun salarié trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $salaries->links() }}
    </div>
</div>
@endsection