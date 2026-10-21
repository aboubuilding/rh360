@extends('layouts.app')

@section('title', 'Sorties des salariés')
@section('page_title', 'Sorties des salariés et solde de tout compte')
@section('page_icon', 'fa-door-open')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Sorties</li>
@endsection

@section('contenu')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        @can('permission', 'paie.manage')
            <div class="card mb-3">
                <div class="card-header"><strong>Nouvelle sortie</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('paie.sorties.store') }}">
                        @csrf
                        <x-field name="salarie_id" label="Salarié" type="select" required
                                 :options="[null => '—'] + $salaries->mapWithKeys(fn ($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                        <x-field name="date_cessation" label="Date de cessation du service" type="date" required />
                        <x-field name="motif" label="Motif" type="select" required :options="\App\Domain\Paie\Models\SortieSalarie::MOTIFS" />
                        <x-field name="preavis" label="Préavis" type="select" required :options="\App\Domain\Paie\Models\SortieSalarie::PREAVIS" />
                        <x-field name="preavis_mois" label="Durée du préavis (mois)" type="number" step="0.5"
                                 help="Vide : 1 mois pour les agents d'exécution, 3 mois pour la maîtrise et les cadres." />
                        <x-field name="faute" label="Faute" type="select" :options="[null => 'Aucune faute grave ni lourde'] + \App\Domain\Paie\Models\SortieSalarie::FAUTES" />
                        <x-field name="montant_convenu" label="Montant convenu (rupture conventionnelle)" type="number" />
                        <x-field name="heures_recherche" label="Heures de recherche d'emploi non utilisées" type="number" step="0.5" />
                        <x-field name="exception_fin_contrat" label="Indemnité de fin de contrat non due (CDI proposé ou refusé, motif économique)" type="checkbox" />
                        <x-field name="observations" label="Observations" type="textarea" />
                        <button class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer la sortie</button>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card">
            <div class="card-header"><strong>Sorties enregistrées</strong></div>
            <ul class="list-group list-group-flush">
                @forelse($sorties as $s)
                    <li class="list-group-item">
                        <a href="{{ route('paie.sorties.index', ['sortie' => $s->id]) }}"><strong>{{ $s->salarie?->nom_complet }}</strong></a>
                        <div class="small text-muted">{{ \App\Domain\Paie\Models\SortieSalarie::MOTIFS[$s->motif] ?? $s->motif }} — le {{ $s->date_cessation->format('d/m/Y') }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune sortie.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        @if($sortie && $calcul)
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                    <strong>Solde de tout compte — {{ $sortie->salarie?->nom_complet }}</strong>
                    <span>{{ \App\Domain\Paie\Models\SortieSalarie::MOTIFS[$sortie->motif] ?? '' }}, cessation le {{ $sortie->date_cessation->format('d/m/Y') }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-6">Salaire mensuel retenu</dt><dd class="col-sm-6">{{ number_format($calcul['details']['salaire_mensuel'], 0, ',', ' ') }} FCFA</dd>
                        <dt class="col-sm-6">Salaire global mensuel moyen (12 mois)</dt><dd class="col-sm-6">{{ number_format($calcul['details']['salaire_global_moyen'], 0, ',', ' ') }} FCFA</dd>
                        <dt class="col-sm-6">Ancienneté</dt><dd class="col-sm-6">{{ rtrim(rtrim(number_format($calcul['details']['anciennete_annees'], 2, ',', ' '), '0'), ',') }} an(s)</dd>
                    </dl>
                </div>
                <table class="table mb-0">
                    <thead>
                        <tr><th>Élément</th><th class="text-end">Montant</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Salaire du mois jusqu'à la cessation</td><td class="text-end text-muted">calculé sur le bulletin</td></tr>
                        @forelse($calcul['lignes'] as [$code, $libelle, $montant])
                            <tr><td>{{ $libelle }}</td><td class="text-end">{{ number_format($montant, 0, ',', ' ') }} FCFA</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Aucune indemnité de sortie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="card-body d-flex flex-wrap gap-2">
                    @if($periode)
                        <a href="{{ route('paie.generation.index', ['periode' => $periode->id]) }}" class="btn btn-outline-primary"><i class="fas fa-file-invoice-dollar"></i> Paie de {{ $periode->libelle }}</a>
                    @else
                        <span class="text-muted small">Générez la paie du mois de la cessation pour produire le bulletin de sortie.</span>
                    @endif
                    @can('permission', 'paie.manage')
                        <form method="POST" action="{{ route('paie.sorties.destroy', $sortie) }}" onsubmit="return confirm('Retirer cette sortie ?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger"><i class="fas fa-trash"></i> Retirer</button>
                        </form>
                    @endcan
                </div>
            </div>
            @foreach($calcul['alertes'] as $alerte)
                <div class="alert alert-warning">{{ $alerte }}</div>
            @endforeach
            <p class="text-muted small">
                Le salaire et les indemnités sont payés dès la cessation du service. Le bulletin de sortie ne porte aucune
                mention valant renonciation du salarié à ses droits.
            </p>
        @else
            <div class="card"><div class="card-body text-center text-muted py-5">Enregistrez une sortie ou choisissez-en une pour voir son solde de tout compte.</div></div>
        @endif
    </div>
</div>
@endsection
