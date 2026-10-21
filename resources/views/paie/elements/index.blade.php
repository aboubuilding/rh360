@extends('layouts.app')

@section('title', 'Éléments de paie des salariés')
@section('page_title', 'Éléments de paie des salariés')
@section('page_icon', 'fa-user-tag')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Éléments des salariés</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-12 col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher un nom ou un matricule…" aria-label="Rechercher">
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Salarié</th>
                <th>Modèle de bulletin</th>
                <th class="text-end">Salaire de base</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salaries as $s)
                @php $r = $modeles[$s->id]; @endphp
                <tr>
                    <td><strong>{{ $s->nom_complet }}</strong> <div class="small text-muted">{{ $s->matricule }}</div></td>
                    <td>
                        @if($r['modele'])
                            {{ $r['modele']->nom }}
                            <span class="badge bg-{{ $r['mode'] === 'manuel' ? 'info' : 'secondary' }}">{{ $r['mode'] === 'manuel' ? 'associé manuellement' : 'selon la catégorie' }}</span>
                        @else
                            <span class="text-danger">{{ $r['anomalie'] }}</span>
                        @endif
                    </td>
                    <td class="text-end">{{ isset($salaireBase[$s->id]) ? number_format($salaireBase[$s->id], 0, ',', ' ') : '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('paie.elements.show', $s) }}" class="btn btn-sm btn-action" title="Éléments de paie"><i class="fas fa-sliders-h"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Aucun salarié actif.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="card-footer">{{ $salaries->links() }}</div>
</div>
@endsection
