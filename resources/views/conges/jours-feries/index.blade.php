@extends('layouts.app')

@section('title', 'Jours fériés')
@section('page_title', 'Calendrier des jours fériés')
@section('page_icon', 'fa-calendar-xmark')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.demandes.index') }}">Congés & Absences</a></li>
    <li>Jours fériés</li>
@endsection

@section('page_actions')
    @can('permission', 'conges.manage')
        <form method="POST" action="{{ route('conges.jours-feries.proposer') }}">
            @csrf
            <input type="hidden" name="annee" value="{{ $annee }}">
            <button class="btn btn-outline-primary"><i class="fas fa-wand-magic-sparkles"></i> Proposer les fêtes de {{ $annee }}</button>
        </form>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('conges.jours-feries.index', ['annee' => $annee - 1]) }}" class="btn btn-outline-secondary"><i class="fas fa-chevron-left"></i> {{ $annee - 1 }}</a>
        <strong class="mx-2">{{ $annee }}</strong>
        <a href="{{ route('conges.jours-feries.index', ['annee' => $annee + 1]) }}" class="btn btn-outline-secondary">{{ $annee + 1 }} <i class="fas fa-chevron-right"></i></a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Les fêtes proposées sont celles à date fixe et les fêtes chrétiennes mobiles. Ajoutez les fêtes dont la date
    est annoncée chaque année (Ramadan, Tabaski…) et les jours fériés décrétés. Ce calendrier sert aux délais
    exprimés en jours ouvrés et au décompte des congés.
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Libellé</th>
                        <th>Origine</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jours as $j)
                        <tr>
                            <td>{{ ucfirst($j->date->translatedFormat('l d F Y')) }}</td>
                            <td><strong>{{ $j->libelle }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $j->origine === 'propose' ? 'warning text-dark' : 'secondary' }}">
                                    {{ \App\Domain\Conges\Models\JourFerie::ORIGINES[$j->origine] ?? $j->origine }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('permission', 'conges.manage')
                                    <form method="POST" action="{{ route('conges.jours-feries.destroy', $j) }}"
                                          onsubmit="return confirm('Retirer ce jour du calendrier ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Retirer"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucun jour férié enregistré pour {{ $annee }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('permission', 'conges.manage')
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header fw-bold">Ajouter un jour férié</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('conges.jours-feries.store') }}">
                        @csrf
                        <x-field name="date" label="Date" type="date" required />
                        <x-field name="libelle" label="Libellé" required />
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
