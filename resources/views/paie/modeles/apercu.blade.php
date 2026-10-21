@extends('layouts.app')

@section('title', 'Aperçu du modèle')
@section('page_title', 'Aperçu — '.$modele->nom)
@section('page_icon', 'fa-eye')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.modeles.index') }}">Modèles de bulletin</a></li>
    <li>Aperçu</li>
@endsection

@section('page_actions')
    <a href="{{ route('paie.modeles.apercu.document', ['modele' => $modele, 'mois' => $mois, 'anciennete' => $anciennete, 'salarie_id' => $salarieChoisi?->id, 'format' => 'pdf']) }}"
       class="btn btn-outline-primary"><i class="fas fa-file-pdf"></i> Télécharger l'aperçu</a>
    @can('permission', 'paie.manage')
        <a href="{{ route('paie.modeles.edit', $modele) }}" class="btn btn-outline-secondary"><i class="fas fa-edit"></i> Modifier le modèle</a>
    @endcan
@endsection

@section('contenu')
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><strong>Paramètres de l'aperçu</strong></div>
            <div class="card-body">
                <form method="GET" action="{{ route('paie.modeles.apercu', $modele) }}">
                    <x-field name="mois" label="Mois" type="month" :value="$mois" />
                    <x-field name="salarie_id" label="Salarié" type="select" :value="$salarieChoisi?->id"
                             :options="[null => 'Salarié type (minimum de la catégorie)'] + $salaries->mapWithKeys(fn ($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                    <x-field name="anciennete" label="Ancienneté du salarié type (années)" type="number" :value="$anciennete" />
                    <button class="btn btn-primary w-100"><i class="fas fa-sync"></i> Actualiser l'aperçu</button>
                </form>
                <p class="text-muted small mt-3 mb-0">
                    L'aperçu n'enregistre rien. Pour un salarié réel, son bulletin du mois est calculé avec ce modèle,
                    sans heures supplémentaires ni saisies variables. Cotisations et impôt suivent les paramètres en vigueur.
                </p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><strong>Rubriques du modèle</strong></div>
            <ul class="list-group list-group-flush">
                @foreach($modele->rubriquesActives as $l)
                    <li class="list-group-item d-flex justify-content-between gap-2">
                        <span>{{ $l->rubrique?->nom }} <small class="text-muted">{{ $l->rubrique?->code }}</small></span>
                        <span class="text-nowrap">
                            @if((float) $l->montant_defaut > 0)
                                {{ number_format($l->montant_defaut, 0, ',', ' ') }}
                            @else
                                <small class="text-muted">calculée ou individuelle</small>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($anomalies)
            <div class="alert alert-warning mt-3">
                <strong>Points d'attention :</strong>
                <ul class="mb-0">
                    @foreach($anomalies as $a)
                        <li>{{ $a['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="col-12 col-lg-8">
        <div class="card">
            <iframe title="Aperçu du bulletin" style="width: 100%; height: 900px; border: 0;"
                    src="{{ route('paie.modeles.apercu.document', ['modele' => $modele, 'mois' => $mois, 'anciennete' => $anciennete, 'salarie_id' => $salarieChoisi?->id]) }}"></iframe>
        </div>
    </div>
</div>
@endsection
