@extends('layouts.app')

@section('title', 'Planning annuel des congés')
@section('page_title', 'Planning annuel des congés')
@section('page_icon', 'fa-calendar-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Planning annuel</li>
@endsection

@section('page_actions')
    @can('permission', 'conges.manage')
        <a href="{{ route('conges.planning.modele') }}" class="btn btn-outline-primary">
            <i class="fas fa-download"></i> Modèle Excel
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Année</label>
                <select name="annee" class="form-select" onchange="this.form.submit()">
                    @for($a = now()->year + 1; $a >= now()->year - 3; $a--)
                        <option value="{{ $a }}" @selected($annee === $a)>{{ $a }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Structure</label>
                <select name="structure_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Toutes structures</option>
                    @foreach($structures as $s)
                        <option value="{{ $s->id }}" @selected(request('structure_id') == $s->id)>{{ $s->nom }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($demandes->count() > 0)
    <div class="card mb-3">
        <div class="card-header">
            <strong>Vue calendaire {{ $annee }}</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="font-size: 0.8rem;">
                <thead>
                    <tr>
                        <th style="width: 220px;">Salarié</th>
                        @for($m = 1; $m <= 12; $m++)
                            <th class="text-center">{{ \Carbon\Carbon::create()->month($m)->format('M') }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @php
                        $parSalarie = $demandes->groupBy('salarie_id');
                    @endphp
                    @foreach($parSalarie as $salarieId => $demandesSalarie)
                        <tr>
                            <td>
                                <strong>{{ $demandesSalarie->first()->salarie?->nom_complet }}</strong>
                            </td>
                            @for($m = 1; $m <= 12; $m++)
                                @php
                                    $dansMois = $demandesSalarie->filter(function ($d) use ($m) {
                                        return $d->date_debut && (int) $d->date_debut->month === $m;
                                    });
                                @endphp
                                <td class="text-center">
                                    @foreach($dansMois as $d)
                                        <span class="badge bg-{{ $d->statut->couleur() }} d-block mb-1"
                                              title="{{ $d->typeConge?->nom }} — du {{ $d->date_debut?->format('d/m') }} au {{ $d->date_reprise?->format('d/m') }}">
                                            {{ $d->typeConge?->code ?? $d->typeConge?->nom }}
                                        </span>
                                    @endforeach
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><strong>Détail ({{ $demandes->count() }} demandes)</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Du</th>
                    <th>Au</th>
                    <th>Durée</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($demandes as $d)
                    <tr>
                        <td>{{ $d->salarie?->nom_complet }}</td>
                        <td>{{ $d->typeConge?->nom }}</td>
                        <td>{{ $d->date_debut?->format('d/m/Y') }}</td>
                        <td>{{ $d->date_reprise?->format('d/m/Y') }}</td>
                        <td>{{ number_format($d->duree_jours, 2) }} j</td>
                        <td>
                            <span class="badge bg-{{ $d->statut->couleur() }}">
                                {{ $d->statut->libelle() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        Aucune demande pour cette année.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection