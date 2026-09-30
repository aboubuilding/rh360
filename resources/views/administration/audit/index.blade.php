@extends('layouts.app')

@section('title', 'Journal d\'audit')
@section('page_title', 'Journal d\'audit')
@section('page_icon', 'fa-history')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Journal d'audit</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select name="action" class="form-select">
                    <option value="">Toutes actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="entite" class="form-select">
                    <option value="">Toutes entités</option>
                    @foreach($entites as $entite)
                        <option value="{{ $entite }}" @selected(request('entite') === $entite)>{{ $entite }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="du" value="{{ request('du') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <input type="date" name="au" value="{{ request('au') }}" class="form-control">
            </div>
            <div class="col-md-3">
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
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Entité</th>
                    <th>ID</th>
                    <th class="text-end">Détails</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entrees as $e)
                    <tr>
                        <td>{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $e->utilisateur?->nom_complet ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $e->action }}</span></td>
                        <td>{{ $e->entite }}</td>
                        <td>{{ $e->entite_id }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.audit.show', $e) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune entrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $entrees->links() }}</div>
</div>
@endsection