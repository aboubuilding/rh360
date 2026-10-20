@extends('layouts.app')

@section('title', 'Intérim — ' . $interim->salarie?->nom_complet)
@section('page_title', 'Intérim — ' . $interim->salarie?->nom_complet)
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>{{ $interim->mouvementCarriere?->numero_mouvement ?? '#'.$interim->id }}</li>
@endsection

@section('page_actions')
    @can('gerer', $interim)
        @if($interim->estProlongeable())
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-prolonger">
                <i class="fas fa-clock"></i> Prolonger
            </button>
        @endif
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-decider">
            <i class="fas fa-gavel"></i> Décision de fin
        </button>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modal-cloturer">
            <i class="fas fa-flag-checkered"></i> Clôturer
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-avantage">
            <i class="fas fa-plus"></i> Avantage
        </button>
    @endcan
@endsection

@section('contenu')
@if($interim->statut === \App\Domain\Interim\Enums\StatutInterim::A_REGULARISER)
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        Délai expiré le {{ $interim->date_limite->format('d/m/Y') }} sans décision : un reclassement ou un retour
        doit être décidé (CCIT, art. 13). Cet intérim bloque la validation de la paie.
    </div>
@endif

@foreach($interim->alertes->where('cle', '!=', 'a_regulariser') as $a)
    <div class="alert alert-{{ $a->niveau }}">
        <i class="fas fa-bell"></i> <strong>{{ $a->intitule }}</strong> — {{ $a->message }}
    </div>
@endforeach

<div class="row mb-3">
    @foreach([
        'Statut' => '<span class="badge bg-'.$interim->statut->couleur().' fs-6">'.e($interim->statut->libelle()).'</span>',
        'Début' => e($interim->date_debut->format('d/m/Y')),
        'Fin prévue' => e($interim->date_fin_prevue->format('d/m/Y')),
        'Date limite' => e($interim->date_limite->format('d/m/Y')),
    ] as $titre => $valeur)
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small mb-1">{{ $titre }}</div>
                    <div class="fw-bold">{!! $valeur !!}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Situation d'origine (conservée)</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Salarié</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('personnel.salaries.show', $interim->salarie) }}">{{ $interim->salarie?->nom_complet }}</a>
                    </dd>
                    <dt class="col-sm-5">Poste permanent</dt>
                    <dd class="col-sm-7">{{ $interim->affectationOrigine?->poste?->intitule ?? '—' }}</dd>
                    <dt class="col-sm-5">Catégorie</dt>
                    <dd class="col-sm-7">{{ $interim->categorieOrigine?->libelle ?? '—' }}</dd>
                    <dt class="col-sm-5">Position</dt>
                    <dd class="col-sm-7">{{ $interim->positionOrigine?->code ?? '—' }}</dd>
                    <dt class="col-sm-5">Salaire de base</dt>
                    <dd class="col-sm-7">{{ $interim->salaire_base_origine !== null ? number_format($interim->salaire_base_origine, 0, ',', ' ').' FCFA' : '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Poste occupé par intérim</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $interim->posteOccupe?->intitule }} <small class="text-muted">{{ $interim->posteOccupe?->structure?->nom }}</small></dd>
                    <dt class="col-sm-5">Catégorie</dt>
                    <dd class="col-sm-7">{{ $interim->categorieOccupee?->libelle }}</dd>
                    <dt class="col-sm-5">Titulaire remplacé</dt>
                    <dd class="col-sm-7">{{ $interim->titulaire?->nom_complet ?? '—' }}</dd>
                    <dt class="col-sm-5">Motif</dt>
                    <dd class="col-sm-7">
                        {{ $interim->motif->libelle() }}
                        @if($interim->precision_motif)<br><small class="text-muted">{{ $interim->precision_motif }}</small>@endif
                    </dd>
                    <dt class="col-sm-5">Fin du motif</dt>
                    <dd class="col-sm-7">{{ $interim->date_fin_motif?->format('d/m/Y') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($interim->estClos())
            <hr>
            <dl class="row mb-0">
                <dt class="col-sm-3">Fin réelle</dt>
                <dd class="col-sm-3">{{ $interim->date_fin_reelle?->format('d/m/Y') }}</dd>
                <dt class="col-sm-3">Cause</dt>
                <dd class="col-sm-3">{{ $interim->decision_fin?->libelle() ?? $interim->cause_fin?->libelle() }}</dd>
            </dl>
        @endif
    </div>
</div>

@if($interim->avantages->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header">Avantages du poste occupé (saisis à la main — CCIT, art. 13)</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Rubrique</th><th class="text-end">Montant mensuel</th><th>Période</th><th>Accord</th></tr></thead>
                <tbody>
                    @foreach($interim->avantages as $a)
                        <tr>
                            <td>{{ $a->rubrique?->nom }}</td>
                            <td class="text-end">{{ number_format($a->montant_mensuel, 0, ',', ' ') }} FCFA</td>
                            <td>{{ $a->date_debut->format('d/m/Y') }} → {{ $a->date_fin?->format('d/m/Y') ?? '…' }}</td>
                            <td>{{ $a->reference_accord }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header">Historique des décisions</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Décision</th><th>Par</th><th>Détail</th><th>Pièce</th></tr></thead>
            <tbody>
                @foreach($interim->decisions as $d)
                    <tr>
                        <td>{{ $d->decide_le->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->type_decision->libelle() }}</td>
                        <td>{{ $d->decidePar?->nom_complet ?? $d->decidePar?->identifiant ?? 'Système' }}</td>
                        <td><small>{{ $d->commentaire }}</small></td>
                        <td>
                            @if($d->chemin_piece)
                                <a href="{{ route('carriere.interims.piece', [$interim, $d]) }}"><i class="fas fa-paperclip"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@can('gerer', $interim)
    @include('carriere.interims.partials._modal-prolonger')
    @include('carriere.interims.partials._modal-cloturer')
    @include('carriere.interims.partials._modal-decider')
    @include('carriere.interims.partials._modal-avantage')
@endcan
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    ['#form-prolonger', '#form-cloturer', '#form-decider', '#form-avantage'].forEach(function (selecteur) {
        soumettreFormulaireInterim(selecteur, function (r) {
            bootstrap.Modal.getInstance($(selecteur).closest('.modal')[0])?.hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        });
    });

    $('#form-decider [name="decision"]').on('change', function () {
        $('#bloc-reclassement').toggle(this.value === 'reclassement');
    }).trigger('change');
});
</script>
@endpush
