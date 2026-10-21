@extends('layouts.app')

@section('title', 'Paie de '.$salarie->nom_complet)
@section('page_title', 'Paie de '.$salarie->nom_complet)
@section('page_icon', 'fa-user-tag')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.elements.index') }}">Éléments des salariés</a></li>
    <li>{{ $salarie->nom_complet }}</li>
@endsection

@section('page_actions')
    @if($resolution['modele'])
        <a href="{{ route('paie.modeles.apercu', [$resolution['modele'], 'salarie_id' => $salarie->id]) }}" class="btn btn-outline-primary">
            <i class="fas fa-eye"></i> Aperçu de son bulletin
        </a>
    @endif
@endsection

@section('contenu')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                <strong>Modèle de bulletin appliqué</strong>
                @if($resolution['modele'])
                    <span>{{ $resolution['modele']->nom }}
                        <span class="badge bg-{{ $resolution['mode'] === 'manuel' ? 'info' : 'secondary' }}">{{ $resolution['mode'] === 'manuel' ? 'associé manuellement' : 'selon la catégorie' }}</span>
                    </span>
                @else
                    <span class="text-danger">{{ $resolution['anomalie'] }}</span>
                @endif
            </div>
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Rubrique</th>
                        <th>Origine du montant</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $affiches = []; @endphp
                    @foreach($lignesModele as $l)
                        @continue(! $l->rubrique)
                        @php $e = $elements->get($l->rubrique_id); $affiches[] = $l->rubrique_id; @endphp
                        <tr>
                            <td>{{ $l->rubrique->nom }} <small class="text-muted">{{ $l->rubrique->code }}</small></td>
                            <td>
                                @if(in_array($l->rubrique->code, ['PRIME-ANC', 'CNSS', 'AMU', 'IRPP'], true))
                                    <span class="text-muted">Calculée par la paie</span>
                                @elseif($e?->exclu)
                                    <span class="badge bg-dark">Exclue pour ce salarié</span>
                                @elseif($e)
                                    Individuel
                                    @if($e->debut || $e->fin)
                                        <small class="text-muted">({{ $e->debut ? 'du '.$e->debut->format('d/m/Y') : '' }} {{ $e->fin ? 'au '.$e->fin->format('d/m/Y') : '' }})</small>
                                    @endif
                                @else
                                    Défaut du modèle
                                @endif
                            </td>
                            <td class="text-end">
                                @if(in_array($l->rubrique->code, ['PRIME-ANC', 'CNSS', 'AMU', 'IRPP'], true))
                                    —
                                @else
                                    {{ number_format($e && ! $e->exclu ? $e->montant : $l->montant_defaut, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if($e && $e->rubrique?->code !== 'SAL-BASE')
                                    @can('permission', 'paie.manage')
                                        <form method="POST" action="{{ route('paie.elements.destroy', [$salarie, $e]) }}" onsubmit="return confirm('Revenir au montant du modèle ?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-action text-danger" title="Revenir au modèle"><i class="fas fa-undo"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @foreach($elements->reject(fn ($e) => in_array($e->rubrique_id, $affiches, true)) as $e)
                        <tr>
                            <td>{{ $e->rubrique?->nom }} <small class="text-muted">{{ $e->rubrique?->code }}</small></td>
                            <td>
                                Individuel, hors modèle
                                @if($e->debut || $e->fin)
                                    <small class="text-muted">({{ $e->debut ? 'du '.$e->debut->format('d/m/Y') : '' }} {{ $e->fin ? 'au '.$e->fin->format('d/m/Y') : '' }})</small>
                                @endif
                            </td>
                            <td class="text-end">{{ $e->exclu ? '—' : number_format($e->montant, 0, ',', ' ') }}</td>
                            <td class="text-end">
                                @if($e->rubrique?->code !== 'SAL-BASE')
                                    @can('permission', 'paie.manage')
                                        <form method="POST" action="{{ route('paie.elements.destroy', [$salarie, $e]) }}" onsubmit="return confirm('Retirer cet élément ?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-action text-danger" title="Retirer"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header"><strong>Associations manuelles à un modèle</strong></div>
            <ul class="list-group list-group-flush">
                @forelse($affectations as $a)
                    <li class="list-group-item d-flex flex-wrap justify-content-between gap-2">
                        <span>
                            {{ $a->modele?->nom }} — du {{ $a->debut->format('d/m/Y') }} {{ $a->fin ? 'au '.$a->fin->format('d/m/Y') : '' }}
                            <div class="small text-muted">{{ $a->motif }}</div>
                        </span>
                        @can('permission', 'paie.manage')
                            <form method="POST" action="{{ route('paie.elements.dissocier', [$salarie, $a]) }}" onsubmit="return confirm('Retirer cette association ?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-unlink"></i> Retirer</button>
                            </form>
                        @endcan
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune : le modèle de la catégorie du salarié s'applique.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @can('permission', 'paie.manage')
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><strong>Définir un élément</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('paie.elements.store', $salarie) }}">
                        @csrf
                        <x-field name="rubrique_id" label="Rubrique" type="select" required
                                 :options="[null => '—'] + $rubriques->mapWithKeys(fn ($r) => [$r->id => $r->nom.' ('.$r->code.')'])->all()" />
                        <x-field name="montant" label="Montant mensuel (FCFA)" type="number" />
                        <div class="row g-2">
                            <div class="col-6"><x-field name="debut" label="Du" type="date" /></div>
                            <div class="col-6"><x-field name="fin" label="Au" type="date" /></div>
                        </div>
                        <x-field name="exclu" label="Exclure cette rubrique du bulletin du salarié" type="checkbox" />
                        <x-field name="observations" label="Observations" />
                        <button class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer</button>
                    </form>
                    <p class="text-muted small mt-2 mb-0">
                        Le salaire de base ne peut pas être inférieur au minimum de la position du salarié. Sans dates,
                        l'élément vaut chaque mois ; un élément mensuel est proratisé sur ses jours de validité.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Associer à un autre modèle</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('paie.elements.associer', $salarie) }}">
                        @csrf
                        <x-field name="modele_id" label="Modèle" type="select" required
                                 :options="[null => '—'] + $modeles->pluck('nom', 'id')->all()" />
                        <div class="row g-2">
                            <div class="col-6"><x-field name="debut" label="Du" type="date" required /></div>
                            <div class="col-6"><x-field name="fin" label="Au" type="date" /></div>
                        </div>
                        <x-field name="motif" label="Motif" type="textarea" required />
                        <button class="btn btn-outline-primary w-100"><i class="fas fa-link"></i> Associer</button>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
