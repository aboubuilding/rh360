@extends('layouts.app')

@section('title', 'Mission ' . $mission->reference)
@section('page_title', 'Mission ' . $mission->reference . ' — ' . $mission->nom_interimaire)
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.missions.index') }}">Missions d'intérim</a></li>
    <li>{{ $mission->reference }}</li>
@endsection

@section('page_actions')
    @if(! $mission->statut->estFinal())
        @can('gerer', $mission)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-checklist">
                <i class="fas fa-hard-hat"></i> Checklist SST
            </button>
            @if($mission->statut === \App\Domain\Interim\Enums\StatutMission::PLANIFIEE)
                <form method="POST" action="{{ route('carriere.interims.missions.demarrer', $mission) }}" id="form-demarrer" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Démarrer</button>
                </form>
            @endif
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-renouveler"><i class="fas fa-redo"></i> Renouveler</button>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modal-terminer"><i class="fas fa-flag-checkered"></i> Terminer</button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-annuler"><i class="fas fa-ban"></i> Annuler</button>
        @endcan
    @endif
@endsection

@section('contenu')
@foreach($mission->alertes as $a)
    <div class="alert alert-{{ $a->niveau }}"><i class="fas fa-bell"></i> <strong>{{ $a->intitule }}</strong> — {{ $a->message }}</div>
@endforeach
@if($mission->alerte_salaire)
    <div class="alert alert-warning"><i class="fas fa-coins"></i> {{ $mission->alerte_salaire }}</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Mission</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Statut</dt><dd class="col-sm-7"><span class="badge bg-{{ $mission->statut->couleur() }}">{{ $mission->statut->libelle() }}</span></dd>
                    <dt class="col-sm-5">Agence</dt><dd class="col-sm-7">{{ $mission->agence?->raison_sociale }}<br><small class="text-muted">Agrément {{ $mission->agence?->numero_agrement ?? '—' }}</small></dd>
                    <dt class="col-sm-5">Poste</dt><dd class="col-sm-7">{{ $mission->poste?->intitule }} <small class="text-muted">{{ $mission->poste?->categorieClassification?->libelle }}</small></dd>
                    <dt class="col-sm-5">Qualification</dt><dd class="col-sm-7">{{ $mission->qualification_convenue }}</dd>
                    <dt class="col-sm-5">Travail déterminé</dt><dd class="col-sm-7">{{ $mission->travail_determine }}</dd>
                    <dt class="col-sm-5">Motif</dt><dd class="col-sm-7">{{ $mission->type_motif->libelle() }}<br><small class="text-muted">{{ $mission->motif }}</small></dd>
                    <dt class="col-sm-5">Salarié remplacé</dt><dd class="col-sm-7">{{ $mission->titulaireRemplace?->nom_complet ?? '—' }}</dd>
                    <dt class="col-sm-5">Période</dt><dd class="col-sm-7">{{ $mission->date_debut->format('d/m/Y') }} → {{ $mission->date_fin->format('d/m/Y') }}{{ $mission->nombre_renouvellements ? ' ('.$mission->nombre_renouvellements.' renouvellement(s))' : '' }}</dd>
                    @if($mission->date_fin_reelle)<dt class="col-sm-5">Fin réelle</dt><dd class="col-sm-7">{{ $mission->date_fin_reelle->format('d/m/Y') }}</dd>@endif
                    <dt class="col-sm-5">Salaire convenu</dt><dd class="col-sm-7">{{ $mission->salaire_convenu !== null ? number_format($mission->salaire_convenu, 0, ',', ' ').' FCFA' : '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Checklist SST — santé, sécurité et hygiène (entreprise utilisatrice)</div>
            <ul class="list-group list-group-flush">
                @foreach(['sst_accueil_le' => 'Accueil sécurité', 'sst_epi_remis_le' => 'EPI remis', 'sst_consignes_le' => 'Consignes du poste'] as $champ => $libelle)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $libelle }}</span>
                        @if($mission->$champ)
                            <span class="text-success"><i class="fas fa-check"></i> {{ $mission->$champ->format('d/m/Y') }}</span>
                        @else
                            <span class="text-danger"><i class="fas fa-times"></i> à faire</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($mission->sst_valide_le)
                <div class="card-footer small text-muted">Validée le {{ $mission->sst_valide_le->format('d/m/Y H:i') }} par {{ $mission->sstValidePar?->nom_complet ?? $mission->sstValidePar?->identifiant }}</div>
            @endif
        </div>

        <div class="card mb-3">
            <div class="card-header">Factures de l'agence</div>
            <ul class="list-group list-group-flush">
                @forelse($mission->factures as $f)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $f->numero_facture }} du {{ $f->date_facture->format('d/m/Y') }}</span>
                        <span>{{ number_format($f->montant, 0, ',', ' ') }} FCFA</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune facture rattachée.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Historique</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Décision</th><th>Par</th><th>Commentaire</th></tr></thead>
            <tbody>
                @foreach($mission->decisions as $d)
                    <tr>
                        <td>{{ $d->decide_le->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->type_decision->libelle() }}</td>
                        <td>{{ $d->decidePar?->nom_complet ?? $d->decidePar?->identifiant ?? 'Système' }}</td>
                        <td><small>{{ $d->commentaire }}</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@can('gerer', $mission)
    @foreach([
        'checklist' => ['Checklist SST', 'carriere.interims.missions.checklist', [
            ['sst_accueil_le', 'Accueil sécurité le', 'date', true], ['sst_epi_remis_le', 'EPI remis le', 'date', true],
            ['sst_consignes_le', 'Consignes du poste remises le', 'date', true], ['piece', 'Pièce (émargement)', 'file', false]]],
        'renouveler' => ['Renouveler la mission', 'carriere.interims.missions.renouveler', [
            ['date_fin', 'Nouvelle date de fin', 'date', true], ['commentaire', 'Commentaire', 'textarea', false], ['piece', 'Pièce', 'file', false]]],
        'terminer' => ['Terminer la mission', 'carriere.interims.missions.terminer', [
            ['date_fin_reelle', 'Date de fin réelle', 'date', true], ['commentaire', 'Commentaire', 'textarea', false]]],
        'annuler' => ['Annuler la mission', 'carriere.interims.missions.annuler', [
            ['motif_annulation', 'Motif d\'annulation', 'textarea', true]]],
    ] as $cle => [$titre, $route, $champs])
        <div class="modal fade" id="modal-{{ $cle }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="form-{{ $cle }}" action="{{ route($route, $mission) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $titre }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @foreach($champs as [$nom, $libelle, $type, $requis])
                                <x-field :name="$nom" :label="$libelle" :type="$type" :required="$requis" />
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endcan
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    ['#form-checklist', '#form-renouveler', '#form-terminer', '#form-annuler', '#form-demarrer'].forEach(function (selecteur) {
        soumettreFormulaireInterim(selecteur, function (r) {
            const modale = $(selecteur).closest('.modal')[0];
            if (modale) { bootstrap.Modal.getInstance(modale)?.hide(); }
            if (r.alerte) { window.showToast(r.alerte, 'warning'); }
            window.showToastThenReload(r.message || 'Enregistré.');
        });
    });
});
</script>
@endpush
