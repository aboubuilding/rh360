@extends('layouts.app')

@section('title', 'Habilitations')
@section('page_title', 'Habilitations professionnelles')
@section('page_icon', 'fa-certificate')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>Habilitations</li>
@endsection

@section('page_actions')
    @can('permission', 'habilitations.manage')
        <a href="{{ route('sst.habilitations.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle habilitation
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, catégorie, salarié...">
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="echeance" class="form-select">
                    <option value="">Toutes échéances</option>
                    <option value="proche" @selected(request('echeance') === 'proche')>Expire sous 60 j</option>
                    <option value="expirees" @selected(request('echeance') === 'expirees')>Expirées</option>
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
                    <th>Catégorie</th>
                    <th>Intitulé</th>
                    <th>Émetteur</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($habilitations as $h)
                    @php
                        $expireBientot = $h->date_fin && $h->date_fin->isFuture() && $h->date_fin->diffInDays(now()) <= 60;
                    @endphp
                    <tr class="{{ $expireBientot ? 'table-warning' : ($h->estExpiree() ? 'table-danger' : '') }}">
                        <td>{{ $h->salarie?->nom_complet }}</td>
                        <td>{{ $h->categorie }}</td>
                        <td>{{ $h->intitule }}</td>
                        <td>{{ $h->emetteur ?? '—' }}</td>
                        <td>{{ $h->date_debut?->format('d/m/Y') }}</td>
                        <td>
                            {{ $h->date_fin?->format('d/m/Y') ?? '—' }}
                            @if($expireBientot)
                                <span class="badge bg-warning text-dark">J-{{ $h->joursRestants() }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $h->statut->couleur() }}">
                                {{ $h->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sst.habilitations.show', $h) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune habilitation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $habilitations->links() }}</div>
</div>
@endsection