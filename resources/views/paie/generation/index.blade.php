@extends('layouts.app')

@section('title', 'Générer la paie')
@section('page_title', 'Générer la paie du mois')
@section('page_icon', 'fa-file-invoice-dollar')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Générer la paie</li>
@endsection

@section('contenu')
@php
    $nomsMois = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
@endphp

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><strong>1. Choisir la période</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('paie.generation.generer') }}" id="form-generer">
                    @csrf
                    <div class="row g-2">
                        <div class="col-7">
                            <x-field name="mois" label="Mois" type="select" required :value="$mois" :options="$nomsMois" />
                        </div>
                        <div class="col-5">
                            <x-field name="annee" label="Année" type="number" required :value="$annee" />
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        La période est ouverte si elle n'existe pas encore. Tous les bulletins des salariés actifs sont
                        calculés ou recalculés, tant que la période n'est pas validée.
                    </p>
                    @can('permission', 'paie.calculer')
                        <button class="btn btn-primary w-100"><i class="fas fa-cogs"></i> 2. Générer les bulletins</button>
                    @endcan
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><strong>Périodes récentes</strong></div>
            <ul class="list-group list-group-flush">
                @forelse($periodes as $p)
                    <li class="list-group-item d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('paie.generation.index', ['periode' => $p->id]) }}">{{ $p->libelle }}</a>
                        <span>
                            <span class="badge bg-{{ $p->statut->couleur() }}">{{ $p->statut->libelle() }}</span>
                            <small class="text-muted">{{ $p->bulletins_count }} bulletin(s)</small>
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune période.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        @if($periode)
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong>3. Bulletins de {{ $periode->libelle }}</strong>
                    <span class="badge bg-{{ $periode->statut->couleur() }}">{{ $periode->statut->libelle() }}</span>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @if($periode->bulletins->isNotEmpty())
                        <a href="{{ route('paie.generation.telecharger', $periode) }}" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Tous les bulletins (un PDF)
                        </a>
                        <a href="{{ route('paie.generation.telecharger', [$periode, 'format' => 'zip']) }}" class="btn btn-outline-success">
                            <i class="fas fa-file-archive"></i> Un PDF par salarié (ZIP)
                        </a>
                    @endif
                    <a href="{{ route('paie.periodes.show', $periode) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list-alt"></i> Saisies, validation et journal de la période
                    </a>
                </div>
            </div>

            @include('paie.periodes.partials._anomalies', ['anomalies' => $periode->anomalies])

            <div class="card">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Salarié</th>
                            <th class="text-end">Brut</th>
                            <th class="text-end">Retenues</th>
                            <th class="text-end">Net à payer</th>
                            <th class="text-end">Bulletin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periode->bulletins->sortBy(fn ($b) => $b->salarie?->nom_complet) as $b)
                            <tr>
                                <td>
                                    <strong>{{ $b->salarie?->nom_complet }}</strong>
                                    <div class="small text-muted">{{ $b->salarie?->matricule }} {{ $b->categorie_libelle ? '— '.$b->categorie_libelle : '' }}</div>
                                </td>
                                <td class="text-end">{{ number_format($b->montant_brut, 0, ',', ' ') }}</td>
                                <td class="text-end text-danger">{{ number_format($b->montant_retenues, 0, ',', ' ') }}</td>
                                <td class="text-end fw-bold">{{ number_format($b->montant_net, 0, ',', ' ') }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('paie.bulletins.show', $b) }}" class="btn btn-sm btn-action" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('paie.bulletins.pdf', $b) }}" class="btn btn-sm btn-action" title="Télécharger le PDF"><i class="fas fa-download"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucun bulletin : cliquez sur « Générer les bulletins ».</td></tr>
                        @endforelse
                    </tbody>
                    @if($periode->bulletins->isNotEmpty())
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td>Total ({{ $periode->bulletins->count() }})</td>
                                <td class="text-end">{{ number_format($periode->bulletins->sum('montant_brut'), 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($periode->bulletins->sum('montant_retenues'), 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($periode->bulletins->sum('montant_net'), 0, ',', ' ') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                    <p class="mb-0">Choisissez un mois et cliquez sur « Générer les bulletins », ou ouvrez une période récente.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
