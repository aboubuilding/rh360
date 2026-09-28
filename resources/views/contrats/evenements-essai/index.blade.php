@extends('layouts.app')

@section('title', 'Événements d\'essai')
@section('page_title', 'Événements de période d\'essai')
@section('page_icon', 'fa-hourglass-half')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Contrats</li>
    <li>Événements d'essai</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="nature" class="form-select">
                    <option value="">Toutes natures</option>
                    @foreach($natures as $val => $lib)
                        <option value="{{ $val }}" @selected(request('nature') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    <option value="pending" @selected(request('statut') === 'pending')>En attente</option>
                    <option value="approved" @selected(request('statut') === 'approved')>Validé</option>
                    <option value="rejected" @selected(request('statut') === 'rejected')>Refusé</option>
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
                    <th>Contrat</th>
                    <th>Salarié</th>
                    <th>Nature</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th>Déclaré par</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evenements as $e)
                    <tr>
                        <td>
                            @if($e->contrat)
                                <a href="{{ route('contrats.contrats.show', $e->contrat) }}">
                                    <code>{{ $e->contrat->reference }}</code>
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $e->contrat?->salarie?->nom_complet ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $e->nature->couleur() }}">
                                {{ $e->nature->libelle() }}
                            </span>
                        </td>
                        <td>
                            {{ $e->details['date_debut'] ?? '—' }}
                            @if($e->details['date_fin'] ?? null)
                                → {{ $e->details['date_fin'] }}
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $e->statut->couleur() }}">
                                {{ $e->statut->libelle() }}
                            </span>
                        </td>
                        <td>{{ $e->creePar?->nom_complet ?? '—' }}</td>
                        <td class="text-end">
                            @if($e->contrat)
                                <a href="{{ route('contrats.contrats.show', $e->contrat) }}"
                                   class="btn btn-sm btn-action">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun événement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $evenements->links() }}</div>
</div>
@endsection