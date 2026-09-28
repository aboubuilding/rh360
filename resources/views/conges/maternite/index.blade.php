@extends('layouts.app')

@section('title', 'Maternité & paternité')
@section('page_title', 'Registre confidentiel — Maternité & paternité')
@section('page_icon', 'fa-baby')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Maternité & paternité</li>
@endsection

@section('page_actions')
    <a href="{{ route('conges.maternite.exporter') }}" class="btn btn-secondary">
        <i class="fas fa-file-excel"></i> Exporter
    </a>
    @can('permission', 'conges.manage')
        <a href="{{ route('conges.maternite.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle déclaration
        </a>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-lock"></i>
    <strong>Registre confidentiel.</strong> Accès restreint aux utilisateurs disposant de la
    permission <code>sensitive.social_health</code>.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, prénoms...">
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
                    <th>Salariée</th>
                    <th>Date déclaration</th>
                    <th>Date prévue accouchement</th>
                    <th>Risque poste</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dossiers as $d)
                    <tr>
                        <td>
                            <strong>{{ $d->salarie?->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $d->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $d->date_declaration?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $d->date_prevue_accouchement?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($d->risque_poste_identifie)
                                <span class="badge bg-warning text-dark">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $d->statut->couleur() }}">
                                {{ $d->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('conges.maternite.show', $d) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun dossier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $dossiers->links() }}</div>
</div>
@endsection