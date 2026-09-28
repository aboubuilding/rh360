@extends('layouts.app')

@section('title', 'Équipements de protection individuelle')
@section('page_title', 'Dotations EPI')
@section('page_icon', 'fa-shield-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Santé & Sécurité</li>
    <li>EPI</li>
@endsection

@section('page_actions')
    @can('permission', 'ppe.manage')
        <a href="{{ route('sst.epi.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouvelle dotation
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Intitulé, numéro série, salarié...">
            </div>
            <div class="col-md-3">
                <select name="categorie" class="form-select">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $val => $lib)
                        <option value="{{ $val }}" @selected(request('categorie') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
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
                    <th>Salarié</th>
                    <th>Catégorie</th>
                    <th>Équipement</th>
                    <th>Quantité</th>
                    <th>N° série</th>
                    <th>Remise le</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dotations as $d)
                    <tr class="{{ $d->estExpire() ? 'table-warning' : '' }}">
                        <td>{{ $d->salarie?->nom_complet }}</td>
                        <td>{{ $d->categorie?->libelle() }}</td>
                        <td>{{ $d->intitule }}</td>
                        <td>{{ $d->quantite }} {{ $d->unite }}</td>
                        <td>{{ $d->numero_serie ?? '—' }}</td>
                        <td>{{ $d->date_remise?->format('d/m/Y') }}</td>
                        <td>{{ $d->date_expiration?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $d->statut->couleur() }}">
                                {{ $d->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sst.epi.show', $d) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune dotation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $dotations->links() }}</div>
</div>
@endsection